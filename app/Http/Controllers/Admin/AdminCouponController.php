<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Coupon;
use App\Models\HostingPlan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminCouponController extends Controller
{
    /**
     * Display directory of promo coupons, discount rules, and redemption statistics.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = Coupon::query();

        // Search Filter
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Type Filter
        if ($type = $request->input('type')) {
            if ($type !== 'all') {
                $query->where('type', $type);
            }
        }

        // Status Filter
        if ($status = $request->input('status')) {
            if ($status === 'active') {
                $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    });
            } elseif ($status === 'expired') {
                $query->where('expires_at', '<=', now());
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $coupons = $query->latest('id')->paginate(15)->withQueryString();

        // 4 Clean 3-Tier Metric Stats
        $allCoupons = Coupon::all();
        $activeCoupons = $allCoupons->filter(fn($c) => $c->isValid());
        $totalRedemptions = $allCoupons->sum('usage_count');

        $percentCoupons = $allCoupons->where('type', 'percentage');
        $avgPercent = $percentCoupons->count() > 0 ? round($percentCoupons->avg('value'), 1) : 0;

        $stats = [
            'total_coupons' => $allCoupons->count(),
            'active_coupons' => $activeCoupons->count(),
            'total_redemptions' => $totalRedemptions,
            'avg_percent' => $avgPercent,
        ];

        // All Hosting Plans for target plan multi-select
        $plans = HostingPlan::select('id', 'name', 'slug', 'price_monthly', 'price_yearly')
            ->orderBy('sort_order', 'asc')
            ->get();

        return Inertia::render('Admin/Billing/Coupons/Index', [
            'coupons' => $coupons,
            'stats' => $stats,
            'plans' => $plans,
            'filters' => $request->only(['search', 'type', 'status']),
        ]);
    }

    /**
     * Store new promo coupon code.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:coupons,code'],
            'type' => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'numeric', 'min:0.01'],
            'applies_to' => ['required', 'in:all,specific_plans,renewals_only,first_order_only'],
            'plan_ids' => ['nullable', 'array'],
            'plan_ids.*' => ['exists:hosting_plans,id'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'user_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['user_limit'] = $validated['user_limit'] ?? 1;

        $coupon = Coupon::create($validated);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'coupon_created',
            'description' => "Created promo coupon '{$coupon->code}' ({$coupon->value} {$coupon->type}).",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['code' => $coupon->code, 'type' => $coupon->type, 'value' => $coupon->value],
        ]);

        return redirect()->route('admin.billing.coupons')
            ->with('success', "Coupon '{$coupon->code}' created successfully.");
    }

    /**
     * Show single coupon details.
     */
    public function show(Coupon $coupon): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        return response()->json([
            'coupon' => $coupon,
            'is_valid' => $coupon->isValid()
        ]);
    }

    /**
     * Update existing promo coupon.
     */
    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:coupons,code,' . $coupon->id],
            'type' => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'numeric', 'min:0.01'],
            'applies_to' => ['required', 'in:all,specific_plans,renewals_only,first_order_only'],
            'plan_ids' => ['nullable', 'array'],
            'plan_ids.*' => ['exists:hosting_plans,id'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'user_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $coupon->update($validated);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'coupon_updated',
            'description' => "Updated promo coupon '{$coupon->code}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => $validated,
        ]);

        return redirect()->route('admin.billing.coupons')
            ->with('success', "Coupon '{$coupon->code}' updated successfully.");
    }

    /**
     * 1-Click Toggle active status.
     */
    public function toggleStatus(Coupon $coupon): RedirectResponse
    {
        $this->authorize('create', User::class);

        $coupon->update(['is_active' => !$coupon->is_active]);
        $statusStr = $coupon->is_active ? 'activated' : 'deactivated';

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'coupon_status_toggled',
            'description' => "Promo coupon '{$coupon->code}' {$statusStr}.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['is_active' => !$coupon->is_active],
            'new_values' => ['is_active' => $coupon->is_active],
        ]);

        return redirect()->route('admin.billing.coupons')
            ->with('success', "Coupon '{$coupon->code}' {$statusStr}.");
    }

    /**
     * Delete promo coupon.
     */
    public function destroy(Coupon $coupon): RedirectResponse
    {
        $this->authorize('create', User::class);

        $code = $coupon->code;
        $coupon->delete();

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'coupon_deleted',
            'description' => "Deleted promo coupon '{$code}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['code' => $code],
        ]);

        return redirect()->route('admin.billing.coupons')
            ->with('success', "Coupon '{$code}' deleted successfully.");
    }
}
