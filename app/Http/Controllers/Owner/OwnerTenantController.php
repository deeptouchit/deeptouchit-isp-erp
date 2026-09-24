<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\SaasInvoice;
use App\Models\SaasPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Setting;
use App\Services\Audit\TenantAuditService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OwnerTenantController extends Controller
{
    protected TenantAuditService $auditService;

    public function __construct(TenantAuditService $auditService)
    {
        $this->auditService = $auditService;
    }
    /**
     * Display a listing of the ISP Tenants with advanced metrics and filtering.
     */
    public function index(Request $request)
    {
        // 1. Compute Top Enterprise Platform Metrics
        $totalTenants = Tenant::count();
        $today = Carbon::today();

        $activeTenants = Tenant::where('status', 'active')
            ->where(function ($q) use ($today) {
                $q->whereNull('subscription_expires_at')
                  ->orWhere('subscription_expires_at', '>=', $today);
            })
            ->count();

        $suspendedTenants = Tenant::where('status', 'suspended')->count();

        $expiredTenants = Tenant::whereNotNull('subscription_expires_at')
            ->where('subscription_expires_at', '<', $today)
            ->count();

        // Estimated Monthly Recurring Revenue (MRR) from active tenants
        $estimatedMrr = Tenant::where('tenants.status', 'active')
            ->join('saas_plans', 'tenants.saas_plan_id', '=', 'saas_plans.id')
            ->sum('saas_plans.monthly_price');

        // 2. Query Builder with Search and Dynamic Filtering
        $query = Tenant::with(['plan', 'users']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('domain', 'like', "%{$search}%");
            });
        }

        if ($request->filled('plan_id')) {
            $query->where('saas_plan_id', $request->plan_id);
        }

        if ($request->filled('status')) {
            if ($request->status === 'expired') {
                $query->whereNotNull('subscription_expires_at')
                      ->where('subscription_expires_at', '<', $today);
            } elseif ($request->status === 'expiring_soon') {
                $query->whereNotNull('subscription_expires_at')
                      ->where('subscription_expires_at', '>=', $today)
                      ->where('subscription_expires_at', '<=', $today->copy()->addDays(7));
            } else {
                $query->where('status', $request->status);
            }
        }

        // 3. Sorting logic
        $sort = $request->get('sort', 'latest');
        switch ($sort) {
            case 'oldest':
                $query->oldest();
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'expiry_asc':
                $query->orderByRaw('subscription_expires_at IS NULL, subscription_expires_at ASC');
                break;
            case 'expiry_desc':
                $query->orderByRaw('subscription_expires_at IS NULL, subscription_expires_at DESC');
                break;
            case 'latest':
            default:
                $query->latest();
                break;
        }

        $perPage = in_array((int)$request->get('per_page', 15), [10, 15, 25, 50, 100]) ? (int)$request->get('per_page', 15) : 15;
        $tenants = $query->paginate($perPage)->withQueryString();

        $plans = SaasPlan::where('is_active', true)->orderBy('sort_order')->get();

        return view('owner.tenants.index', compact(
            'tenants',
            'plans',
            'totalTenants',
            'activeTenants',
            'suspendedTenants',
            'expiredTenants',
            'estimatedMrr'
        ));
    }

    /**
     * Show the form for creating a new ISP Tenant.
     */
    public function create()
    {
        $plans = SaasPlan::where('is_active', true)->orderBy('sort_order')->get();
        return view('owner.tenants.create', compact('plans'));
    }

    /**
     * Store a newly created ISP Tenant in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'alpha_dash', 'max:50', 'unique:tenants,slug'],
            'domain' => ['nullable', 'string', 'max:255', 'unique:tenants,domain'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255', 'unique:tenants,email'],
            'address' => ['nullable', 'string'],
            'saas_plan_id' => ['required', 'exists:saas_plans,id'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:6'],
            'duration_months' => ['nullable'],
        ]);

        $isTrial = ($request->duration_months === 'trial');
        $trialDays = (int) Setting::get('default_trial_days', 14);
        $durationMonths = $isTrial ? 1 : (int)($request->duration_months ?? 1);
        $expiresAt = $isTrial ? now()->addDays($trialDays) : now()->addMonths($durationMonths);
        $initialStatus = $isTrial ? 'trial' : 'active';

        DB::transaction(function () use ($validated, $expiresAt, $durationMonths, $isTrial, $trialDays, $initialStatus) {
            $tenant = Tenant::create([
                'name' => $validated['name'],
                'company_name' => $validated['company_name'],
                'slug' => strtolower($validated['slug']),
                'domain' => !empty($validated['domain']) ? strtolower(trim($validated['domain'])) : null,
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'address' => $validated['address'] ?? null,
                'saas_plan_id' => $validated['saas_plan_id'],
                'status' => $initialStatus,
                'subscription_expires_at' => $expiresAt,
                'wallet_balance' => 0.00,
            ]);

            User::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['admin_password']),
                'role' => 'isp_admin',
                'status' => 'active',
            ]);

            // 1. Create Subscription
            $plan = SaasPlan::find($validated['saas_plan_id']);
            $startDate = now();
            $periodEnd = $isTrial ? $startDate->copy()->addDays($trialDays) : $startDate->copy()->addMonths($durationMonths)->subDay();
            $nextBillingDate = $periodEnd->copy()->addDay();

            $subscription = \App\Models\TenantSubscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'billing_cycle' => 'monthly',
                'started_at' => $startDate,
                'current_period_start' => $startDate->format('Y-m-d'),
                'current_period_end' => $periodEnd->format('Y-m-d'),
                'next_billing_date' => $nextBillingDate->format('Y-m-d'),
                'status' => $initialStatus,
                'auto_renew' => true,
            ]);

            // 2. Auto-generate Onboarding Subscription Invoice
            $invoiceAmount = $isTrial ? 0.00 : ($plan ? (($plan->monthly_price * $durationMonths) + ($plan->otc_charge ?? 0)) : 0);
            $invoicePrefix = Setting::get('invoice_prefix', 'INV-');
            $invoiceNo = $invoicePrefix . date('Ym') . '-' . strtoupper(Str::random(4)) . rand(10, 99);

            $invoice = SaasInvoice::create([
                'tenant_id' => $tenant->id,
                'tenant_subscription_id' => $subscription->id,
                'saas_plan_id' => $plan ? $plan->id : null,
                'invoice_no' => $invoiceNo,
                'amount' => $invoiceAmount,
                'subtotal' => $invoiceAmount,
                'paid_amount' => $invoiceAmount,
                'due_amount' => 0.00,
                'period_start' => $startDate->format('Y-m-d'),
                'period_end' => $periodEnd->format('Y-m-d'),
                'status' => 'paid',
                'payment_method' => $isTrial ? 'Trial Provision' : 'Online',
                'trx_id' => 'INIT-' . strtoupper(Str::random(8)),
                'due_date' => now()->format('Y-m-d'),
                'paid_at' => now(),
            ]);

            // Create Line item
            \App\Models\SubscriptionInvoiceItem::create([
                'saas_invoice_id' => $invoice->id,
                'description' => $isTrial ? "Free Trial Activation ({$trialDays} Days) - {$plan->name}" : "Initial Onboarding Subscription - {$plan->name} ({$durationMonths} Mo)",
                'quantity' => 1,
                'unit_price' => $invoiceAmount,
                'total_price' => $invoiceAmount,
            ]);

            // Record Audit Log
            $this->auditService->log(
                $tenant,
                'tenant_created',
                "Provisioned new ISP organization '{$tenant->name}' on plan '{$plan->name}' for {$durationMonths} months.",
                ['plan' => $plan->name, 'duration_months' => $durationMonths, 'admin_email' => $validated['admin_email']]
            );
        });

        return redirect()->route('owner.tenants.index')->with('success', "ISP Organization '{$validated['name']}' has been provisioned successfully.");
    }

    /**
     * Display the specified ISP Tenant details.
     */
    public function show(Tenant $tenant)
    {
        $tenant->load([
            'plan', 
            'users', 
            'subscriptions' => fn($q) => $q->with('plan')->latest(),
            'invoices' => fn($q) => $q->with('items')->latest()->limit(15),
            'payments' => fn($q) => $q->latest()->limit(15),
            'wallet.transactions' => fn($q) => $q->latest()->limit(10),
            'activityLogs' => fn($q) => $q->latest()->limit(25),
            'smsLogs' => fn($q) => $q->latest()->limit(25),
        ]);
        $plans = SaasPlan::where('is_active', true)->orderBy('sort_order')->get();

        return view('owner.tenants.show', compact('tenant', 'plans'));
    }

    /**
     * Show the form for editing the specified ISP Tenant.
     */
    public function edit(Tenant $tenant)
    {
        $tenant->load(['plan', 'users']);
        $plans = SaasPlan::where('is_active', true)->orderBy('sort_order')->get();
        return view('owner.tenants.edit', compact('tenant', 'plans'));
    }

    /**
     * Update the specified ISP Tenant in storage.
     */
    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'alpha_dash', 'max:50', 'unique:tenants,slug,' . $tenant->id],
            'domain' => ['nullable', 'string', 'max:255', 'unique:tenants,domain,' . $tenant->id],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255', 'unique:tenants,email,' . $tenant->id],
            'address' => ['nullable', 'string'],
            'saas_plan_id' => ['required', 'exists:saas_plans,id'],
            'status' => ['required', 'in:active,suspended,pending,cancelled'],
            'subscription_expires_at' => ['nullable', 'date'],
            'wallet_balance' => ['nullable', 'numeric', 'min:0'],
        ]);

        $oldStatus = $tenant->status;
        $oldPlanId = $tenant->saas_plan_id;

        $tenant->update([
            'name' => $validated['name'],
            'company_name' => $validated['company_name'],
            'slug' => strtolower($validated['slug']),
            'domain' => !empty($validated['domain']) ? strtolower(trim($validated['domain'])) : null,
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'address' => $validated['address'] ?? null,
            'saas_plan_id' => $validated['saas_plan_id'],
            'status' => $validated['status'],
            'subscription_expires_at' => $validated['subscription_expires_at'] ?? null,
            'wallet_balance' => $validated['wallet_balance'] ?? $tenant->wallet_balance,
        ]);

        // Record Audit Log
        $this->auditService->log(
            $tenant,
            'tenant_updated',
            "Updated profile information for '{$tenant->name}'.",
            ['old_status' => $oldStatus, 'new_status' => $validated['status'], 'plan_changed' => ($oldPlanId != $validated['saas_plan_id'])]
        );

        return redirect()->route('owner.tenants.index')->with('success', "Tenant '{$tenant->name}' profile updated successfully.");
    }

    /**
     * Extend or adjust the subscription validity for a tenant.
     */
    public function extendSubscription(Request $request, Tenant $tenant)
    {
        $request->validate([
            'extension_type' => ['required', 'in:1_month,3_months,6_months,1_year,custom_date'],
            'custom_date' => ['nullable', 'date', 'required_if:extension_type,custom_date'],
            'reactivate_if_suspended' => ['nullable', 'boolean'],
        ]);

        $currentExpiry = $tenant->subscription_expires_at ? Carbon::parse($tenant->subscription_expires_at) : now();
        $baseDate = $currentExpiry->isPast() ? now() : $currentExpiry;

        switch ($request->extension_type) {
            case '1_month':
                $newExpiry = $baseDate->copy()->addMonth();
                break;
            case '3_months':
                $newExpiry = $baseDate->copy()->addMonths(3);
                break;
            case '6_months':
                $newExpiry = $baseDate->copy()->addMonths(6);
                break;
            case '1_year':
                $newExpiry = $baseDate->copy()->addYear();
                break;
            case 'custom_date':
                $newExpiry = Carbon::parse($request->custom_date);
                break;
            default:
                $newExpiry = $baseDate->copy()->addMonth();
                break;
        }

        $updateData = [
            'subscription_expires_at' => $newExpiry->format('Y-m-d'),
        ];

        if ($request->filled('reactivate_if_suspended') || $tenant->status !== 'active') {
            $updateData['status'] = 'active';
        }

        $tenant->update($updateData);

        // Record Audit Log
        $this->auditService->log(
            $tenant,
            'subscription_extended',
            "Extended subscription validity until " . $newExpiry->format('d M, Y') . " ({$request->extension_type}).",
            ['previous_expiry' => $currentExpiry->toDateString(), 'new_expiry' => $newExpiry->toDateString()]
        );

        return back()->with('success', "Subscription for '{$tenant->name}' extended until " . $newExpiry->format('d M, Y') . '.');
    }

    /**
     * Remove the specified ISP Tenant from storage.
     */
    public function destroy(Tenant $tenant)
    {
        $tenantName = $tenant->name;
        
        // Log prior to deletion
        $this->auditService->log(
            $tenant->id,
            'tenant_deleted',
            "Permanently removed ISP Tenant '{$tenantName}' and all associated records."
        );

        DB::transaction(function () use ($tenant) {
            $tenant->users()->delete();
            $tenant->invoices()->delete();
            $tenant->delete();
        });

        return redirect()->route('owner.tenants.index')->with('success', "ISP Tenant '{$tenantName}' and associated records removed successfully.");
    }

    /**
     * Toggle active/suspended status of a tenant.
     */
    public function toggleStatus(Tenant $tenant)
    {
        $newStatus = $tenant->status === 'active' ? 'suspended' : 'active';
        $tenant->update(['status' => $newStatus]);

        $this->auditService->log(
            $tenant,
            'status_toggled',
            "Changed tenant status from '{$tenant->status}' to '{$newStatus}'.",
            ['status' => $newStatus]
        );

        $msg = $newStatus === 'active' ? "Tenant '{$tenant->name}' is now active." : "Tenant '{$tenant->name}' has been suspended.";
        return back()->with('success', $msg);
    }

    /**
     * Impersonate tenant admin to access their ISP dashboard.
     */
    public function impersonate(Tenant $tenant)
    {
        $adminUser = $tenant->users()->where('role', 'isp_admin')->first() ?? $tenant->users()->first();
        if (!$adminUser) {
            return back()->with('error', 'No ISP administrator user found for this tenant.');
        }

        // Record Audit Log
        $this->auditService->log(
            $tenant,
            'impersonated',
            "Super Admin impersonated ISP Administrator '{$adminUser->name}' ({$adminUser->email}).",
            ['admin_user_id' => $adminUser->id]
        );

        // Store owner session info for quick reversal
        session([
            'impersonator_id' => Auth::id(),
            'impersonated_tenant_id' => $tenant->id,
            'impersonated_tenant_name' => $tenant->company_name ?: $tenant->name,
        ]);

        Auth::login($adminUser);
        return redirect()->route('tenant.dashboard')->with('success', "Logged in as administrator for '{$tenant->name}'.");
    }

    /**
     * Leave impersonation and return to SaaS owner console.
     */
    public function leaveImpersonation()
    {
        if (session()->has('impersonator_id')) {
            $ownerId = session('impersonator_id');
            $owner = User::find($ownerId);
            session()->forget(['impersonator_id', 'impersonated_tenant_id', 'impersonated_tenant_name']);

            if ($owner) {
                Auth::login($owner);
                return redirect()->route('owner.dashboard')->with('success', 'Returned to SaaS Owner Console.');
            }
        }
        return redirect()->route('owner.login');
    }
}
