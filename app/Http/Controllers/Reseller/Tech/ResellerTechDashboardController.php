<?php

namespace App\Http\Controllers\Reseller\Tech;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantCustomer;
use App\Models\SupportTicket;
use App\Models\TenantReseller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ResellerTechDashboardController extends Controller
{
    /**
     * Resolve active authenticated Reseller and Tenant.
     */
    protected function getResellerData(): array
    {
        $user = Auth::user();
        $reseller = $user->reseller;
        if (!$reseller && $user->reseller_id) {
            $reseller = TenantReseller::find($user->reseller_id);
        }
        if (!$reseller && $user->tenant_id) {
            $reseller = TenantReseller::where('tenant_id', $user->tenant_id)->first();
        }
        if (!$reseller) {
            $reseller = TenantReseller::first();
        }

        $tenant = $reseller?->tenant ?? ($user->tenant ?? Tenant::first());

        return [$user, $reseller, $tenant];
    }

    /**
     * Display Reseller Field Tech Dashboard.
     */
    public function index(Request $request): View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $tenantId = $tenant?->id;
        $resellerId = $reseller?->id;

        $customerBase = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId);

        $pendingInstallations = (clone $customerBase)->where('status', 'inactive')->orWhereNull('online_status')->count();
        $ticketsQuery = SupportTicket::where('tenant_id', $tenantId)->where('reseller_id', $resellerId);
        
        $assignedTickets = (clone $ticketsQuery)->whereIn('status', ['open', 'in_progress'])->count();
        $resolvedToday = (clone $ticketsQuery)->where('status', 'resolved')->whereDate('updated_at', today())->count();
        $activeSessions = (clone $customerBase)->where('status', 'active')->count();
        $offlineCustomers = (clone $customerBase)->where('status', 'expired')->orWhere('status', 'disconnected')->count();
        $totalCustomers = (clone $customerBase)->count();

        // 6 KPI Summary Cards (AGENTS.md Rule 2.B)
        $stats = [
            'pending_installations' => $pendingInstallations,
            'assigned_tickets' => $assignedTickets,
            'resolved_today' => $resolvedToday,
            'active_sessions' => $activeSessions,
            'offline_customers' => $offlineCustomers,
            'total_customers' => $totalCustomers,
        ];

        $recentTickets = (clone $ticketsQuery)->latest('id')->limit(5)->get();
        $recentCustomers = (clone $customerBase)->with(['package', 'coverageZone'])->latest('id')->limit(5)->get();

        $currencySymbol = '৳';

        return view('reseller.tech.dashboard', compact(
            'tenant',
            'reseller',
            'authUser',
            'stats',
            'recentTickets',
            'recentCustomers',
            'currencySymbol'
        ));
    }
}
