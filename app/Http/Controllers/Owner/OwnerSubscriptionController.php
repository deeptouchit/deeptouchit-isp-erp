<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\SaasPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Services\Billing\RenewalService;
use App\Services\Billing\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OwnerSubscriptionController extends Controller
{
    /**
     * Display a listing of all tenant subscriptions with lifecycle states.
     */
    public function index(Request $request)
    {
        $today = Carbon::today();

        // 1. Subscription Metrics
        $totalSubscriptions = TenantSubscription::count();
        $activeCount = TenantSubscription::where('status', 'active')->count();
        $trialCount = TenantSubscription::where('status', 'trial')->count();
        $gracePeriodCount = TenantSubscription::where('status', 'grace_period')->count();
        $suspendedCount = TenantSubscription::where('status', 'suspended')->count();
        $expiredCount = TenantSubscription::where('status', 'expired')->count();
        $autoRenewCount = TenantSubscription::where('auto_renew', true)->count();

        // 2. Query Builder
        $query = TenantSubscription::with(['tenant', 'plan']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->whereHas('tenant', function ($t) use ($search) {
                $t->where('name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->plan_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('billing_cycle')) {
            $query->where('billing_cycle', $request->billing_cycle);
        }

        if ($request->filled('auto_renew')) {
            $query->where('auto_renew', $request->auto_renew === '1');
        }

        $subscriptions = $query->latest()->paginate(15)->withQueryString();
        $tenants = Tenant::orderBy('name')->get();
        $plans = SaasPlan::where('is_active', true)->orderBy('sort_order')->get();

        return view('owner.subscriptions.index', compact(
            'subscriptions',
            'tenants',
            'plans',
            'totalSubscriptions',
            'activeCount',
            'trialCount',
            'gracePeriodCount',
            'suspendedCount',
            'expiredCount',
            'autoRenewCount'
        ));
    }

    /**
     * Store a new subscription for a tenant.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'plan_id' => ['required', 'exists:saas_plans,id'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'duration_months' => ['required', 'integer', 'min:1', 'max:60'],
            'auto_renew' => ['nullable', 'boolean'],
        ]);

        $tenant = Tenant::findOrFail($validated['tenant_id']);
        $plan = SaasPlan::findOrFail($validated['plan_id']);

        $sub = app(SubscriptionService::class)->createSubscription(
            $tenant,
            $plan,
            $validated['billing_cycle'],
            (int) $validated['duration_months']
        );

        if ($request->has('auto_renew')) {
            $sub->update(['auto_renew' => true]);
        }

        return redirect()->route('owner.subscriptions.index')->with('success', "Subscription provisioned for {$tenant->name}.");
    }

    /**
     * Manually renew a subscription by 1 period.
     */
    public function renew(TenantSubscription $subscription)
    {
        $renewed = app(RenewalService::class)->renewSubscription($subscription);

        return back()->with('success', "Subscription for {$subscription->tenant->name} renewed until {$renewed->current_period_end->format('d M, Y')}.");
    }

    /**
     * Update status and auto-renew flag of a subscription.
     */
    public function updateStatus(Request $request, TenantSubscription $subscription)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:trial,active,grace_period,past_due,suspended,expired,cancelled'],
            'auto_renew' => ['nullable', 'boolean'],
            'current_period_end' => ['nullable', 'date'],
        ]);

        $updateData = [
            'status' => $validated['status'],
            'auto_renew' => $request->has('auto_renew'),
            'suspended_at' => $validated['status'] === 'suspended' ? now() : null,
        ];

        if (!empty($validated['current_period_end'])) {
            $updateData['current_period_end'] = $validated['current_period_end'];
            $updateData['next_billing_date'] = Carbon::parse($validated['current_period_end'])->addDay()->toDateString();
        }

        $subscription->update($updateData);

        if ($subscription->tenant) {
            $subscription->tenant->update([
                'status' => in_array($validated['status'], ['active', 'trial', 'grace_period']) ? 'active' : 'suspended',
                'subscription_expires_at' => $subscription->current_period_end ? $subscription->current_period_end->format('Y-m-d') : $subscription->tenant->subscription_expires_at,
            ]);
        }

        return back()->with('success', "Subscription status updated to " . strtoupper($validated['status']) . ".");
    }
}
