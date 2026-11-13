<?php

namespace App\Services\Billing;

use App\Models\SaasPlan;
use App\Models\Tenant;
use App\Models\TenantCustomer;
use App\Models\TenantInternetPackage;
use App\Models\TenantOlt;
use App\Models\TenantReseller;
use App\Models\TenantRouter;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PlanQuotaService
{
    /**
     * Quota resource definitions mapped cleanly to plan limit columns and live Eloquent models.
     */
    protected const RESOURCE_MAP = [
        'customer' => [
            'column' => 'customer_limit',
            'default' => 500,
            'model' => TenantCustomer::class,
            'label' => 'customers',
        ],
        'reseller' => [
            'column' => 'reseller_limit',
            'default' => 10,
            'model' => TenantReseller::class,
            'label' => 'resellers',
        ],
        'mikrotik' => [
            'column' => 'mikrotik_limit',
            'default' => 2,
            'model' => TenantRouter::class,
            'label' => 'MikroTik routers',
        ],
        'olt' => [
            'column' => 'olt_limit',
            'default' => 1,
            'model' => TenantOlt::class,
            'label' => 'OLT devices',
        ],
    ];

    /**
     * Plan feature boolean gates mapped to SaasPlan attributes.
     */
    protected const FEATURE_MAP = [
        'radius' => [
            'column' => 'allow_radius',
            'label' => 'RADIUS / NAS Authentication',
        ],
        'wireguard' => [
            'column' => 'allow_wireguard',
            'label' => 'WireGuard VPN Tunnel',
        ],
        'snmp' => [
            'column' => 'allow_snmp_monitoring',
            'label' => 'SNMP OLT Monitoring',
        ],
    ];

    /**
     * Resolve active SaaS plan for a tenant.
     */
    public static function getPlan(Tenant $tenant): ?SaasPlan
    {
        $tenant->loadMissing('plan');
        return $tenant->plan;
    }

    /**
     * Return accurate live infrastructure and subscriber counts using real tenant models.
     */
    public static function getUsageSummary(Tenant $tenant): array
    {
        return [
            'customers' => TenantCustomer::where('tenant_id', $tenant->id)->count(),
            'active_customers' => TenantCustomer::where('tenant_id', $tenant->id)->where('status', 'active')->count(),
            'resellers' => TenantReseller::where('tenant_id', $tenant->id)->count(),
            'mikrotik' => TenantRouter::where('tenant_id', $tenant->id)->count(),
            'olt' => TenantOlt::where('tenant_id', $tenant->id)->count(),
            'staff' => User::where('tenant_id', $tenant->id)->count(),
            'packages' => TenantInternetPackage::where('tenant_id', $tenant->id)->count(),
        ];
    }

    /**
     * Evaluate plan quota for a given resource ('customer', 'reseller', 'mikrotik', 'olt').
     */
    public static function checkQuota(Tenant $tenant, string $resource, int $adding = 1): array
    {
        $meta = self::RESOURCE_MAP[$resource] ?? null;
        if (!$meta) {
            return [
                'allowed' => true,
                'limit' => 0,
                'current' => 0,
                'remaining' => PHP_INT_MAX,
                'plan_name' => 'Current Plan',
                'message' => '',
            ];
        }

        $plan = self::getPlan($tenant);
        $planName = $plan?->name ?? __('Current Plan');
        $limit = $plan ? (int) ($plan->{$meta['column']} ?? $meta['default']) : $meta['default'];
        $current = ($meta['model'])::where('tenant_id', $tenant->id)->count();
        $remaining = $limit > 0 ? max(0, $limit - $current) : PHP_INT_MAX;
        $allowed = $limit <= 0 || ($current + $adding) <= $limit;

        $message = $allowed
            ? ''
            : __("You have reached your Plan (:plan) quota limit of :limit :resource. Please upgrade your subscription plan.", [
                'plan' => $planName,
                'limit' => number_format($limit),
                'resource' => __($meta['label']),
            ]);

        return [
            'allowed' => $allowed,
            'limit' => $limit,
            'current' => $current,
            'remaining' => $remaining,
            'is_reached' => $limit > 0 && $current >= $limit,
            'plan_name' => $planName,
            'message' => $message,
        ];
    }

    /**
     * Check whether the tenant's active SaaS plan permits a gated protocol feature.
     */
    public static function hasFeature(Tenant $tenant, string $feature): bool
    {
        $meta = self::FEATURE_MAP[$feature] ?? null;
        if (!$meta) {
            return true;
        }

        $plan = self::getPlan($tenant);
        if (!$plan) {
            return true;
        }

        return (bool) ($plan->{$meta['column']} ?? false);
    }

    /**
     * Build consistent localized feature-locked error text.
     */
    public static function featureLockedMessage(Tenant $tenant, string $feature): string
    {
        $plan = self::getPlan($tenant);
        $planName = $plan?->name ?? __('Current Plan');
        $featureLabel = __(self::FEATURE_MAP[$feature]['label'] ?? ucfirst($feature));

        return __("Your active Plan (:plan) does not include :feature. Please upgrade your subscription plan to unlock this feature.", [
            'plan' => $planName,
            'feature' => $featureLabel,
        ]);
    }

    /**
     * Return uniform JSON or redirect response when plan quota/feature limit denies an action.
     */
    public static function denyResponse(Request $request, string $message, int $status = 403): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson() || $request->ajax() || str_contains((string) $request->header('Accept', ''), 'json')) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status);
        }

        return back()->withErrors(['plan' => $message])->with('error', $message)->withInput();
    }
}

