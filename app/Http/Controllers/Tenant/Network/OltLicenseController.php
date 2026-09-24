<?php

namespace App\Http\Controllers\Tenant\Network;

use App\Http\Controllers\Controller;
use App\Models\TenantOlt;
use App\Services\Network\OltApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;

class OltLicenseController extends Controller
{
    protected OltApiService $oltApiService;

    public function __construct(OltApiService $oltApiService)
    {
        $this->oltApiService = $oltApiService;
    }

    /**
     * OLT License Governance & Lifecycle Management Dashboard
     */
    public function index(Request $request): View
    {
        $tenant = $this->getTenant();

        $query = TenantOlt::where("tenant_id", $tenant->id)->with(["router"]);

        if ($request->filled("search")) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where("name", "like", "%{$s}%")
                  ->orWhere("ip_address", "like", "%{$s}%")
                  ->orWhere("model", "like", "%{$s}%")
                  ->orWhere("vendor", "like", "%{$s}%");
            });
        }

        if ($request->filled("status")) {
            $st = $request->status;
            if ($st === "unlimited") {
                $query->where("license_limit", 0);
            } elseif ($st === "warning") {
                $query->where("license_limit", 1)->where("license_time_hours", "<=", 168);
            } elseif ($st === "active") {
                $query->where("license_limit", 1)->where("license_time_hours", ">", 168);
            }
        }

        $olts = $query->latest("updated_at")->paginate(15)->withQueryString();

        // High-level KPI aggregates
        $allTenantOlts = TenantOlt::where("tenant_id", $tenant->id)->get();
        $totalCount = $allTenantOlts->count();
        $unlimitedCount = $allTenantOlts->where("license_limit", 0)->count();
        $warningCount = $allTenantOlts->filter(fn($o) => $o->license_limit == 1 && $o->license_time_hours <= 168)->count();
        $activeLimitedCount = $allTenantOlts->filter(fn($o) => $o->license_limit == 1 && $o->license_time_hours > 168)->count();

        $stats = [
            "total_olts" => $totalCount,
            "unlimited_licenses" => $unlimitedCount,
            "warning_licenses" => $warningCount,
            "active_limited" => $activeLimitedCount,
        ];

        return view("tenant.network.license", compact("olts", "stats"));
    }

    /**
     * Unlock / Update OLT License via Live API
     */
    public function update(Request $request, TenantOlt $olt): JsonResponse|RedirectResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant || $olt->tenant_id !== $tenant->id) {
            abort(403);
        }

        $request->validate([
            "auth_password" => "required|string",
            "limit_days" => "nullable|integer|min:0|max:3650",
            "auto_renew" => "nullable|boolean",
        ]);

        $authPassword = $request->input("auth_password");
        $limitDays = (int) $request->input("limit_days", 0); // 0 = Unlimited

        $result = $this->oltApiService->updateOltLicense($olt, $authPassword, $limitDays);

        if ($request->has("auto_renew")) {
            $olt->update([
                "license_auto_renew" => (bool) $request->auto_renew,
                "license_renew_days" => $limitDays,
                "license_renew_threshold_hours" => (int) $request->input("renew_threshold_hours", 168),
            ]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($result);
        }

        if ($result["success"]) {
            return back()->with("success", $result["message"]);
        }

        return back()->with("error", $result["message"]);
    }

    /**
     * Refresh live license telemetry from OLT hardware
     */
    public function sync(TenantOlt $olt): JsonResponse|RedirectResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant || $olt->tenant_id !== $tenant->id) {
            abort(403);
        }

        $result = $this->oltApiService->getOltLicenseInfo($olt);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json($result);
        }

        return back()->with("success", "License telemetry synchronized successfully for {$olt->name}.");
    }

    /**
     * Toggle Background Auto-Renewal / Auto-Unlock
     */
    public function toggleAutoRenew(Request $request, TenantOlt $olt): JsonResponse|RedirectResponse
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant || $olt->tenant_id !== $tenant->id) {
            abort(403);
        }

        $request->validate([
            "license_auto_renew" => "required|boolean",
            "license_auth_password" => "nullable|string",
            "license_renew_threshold_hours" => "nullable|integer|min:1",
            "license_renew_days" => "nullable|integer|min:0|max:3650",
        ]);

        $updateData = [
            "license_auto_renew" => (bool) $request->license_auto_renew,
            "license_renew_threshold_hours" => (int) $request->input("license_renew_threshold_hours", 168),
            "license_renew_days" => (int) $request->input("license_renew_days", 0),
        ];

        if ($request->filled("license_auth_password")) {
            $updateData["license_auth_password"] = Crypt::encryptString($request->license_auth_password);
        }

        $olt->update($updateData);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(["success" => true, "message" => "Auto-license management policy saved."]);
        }

        return back()->with("success", "Auto-license management policy saved.");
    }

    protected function getTenant()
    {
        $user = Auth::user();
        $tenant = $user?->tenant;
        if (!$tenant && $user?->tenant_id) {
            $tenant = \App\Models\Tenant::find($user->tenant_id);
        }
        if (!$tenant) {
            $tenant = \App\Models\Tenant::first();
        }
        if (!$tenant) {
            abort(404, "No tenant workspace found.");
        }
        return $tenant;
    }
}
