<?php

namespace App\Http\Controllers\Reseller\Tech;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantCustomer;
use App\Models\SupportTicket;
use App\Models\TenantCoverageZone;
use App\Models\TenantReseller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ResellerTechTaskController extends Controller
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
     * Display Pending Installations for Reseller.
     */
    public function installations(Request $request): View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $tenantId = $tenant?->id;
        $resellerId = $reseller?->id;

        $search = $request->input('search');
        $zoneFilter = $request->input('zone_id');
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }

        $query = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->with(['package', 'coverageZone']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($zoneFilter && $zoneFilter !== 'all') {
            $query->where('zone_id', $zoneFilter);
        }

        $installations = $query->latest('id')->paginate($perPage)->withQueryString();

        $baseQuery = TenantCustomer::where('tenant_id', $tenantId)->where('reseller_id', $resellerId);
        $stats = [
            'pending_installations' => (clone $baseQuery)->where('status', 'inactive')->orWhereNull('online_status')->count(),
            'active_lines' => (clone $baseQuery)->where('status', 'active')->count(),
            'total_subscribers' => (clone $baseQuery)->count(),
            'new_this_month' => (clone $baseQuery)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'due_lines' => (clone $baseQuery)->where('status', 'due')->count(),
            'expired_lines' => (clone $baseQuery)->where('status', 'expired')->count(),
        ];

        $zones = TenantCoverageZone::where('tenant_id', $tenantId)->get();
        $currencySymbol = '৳';

        return view('reseller.tech.installations', compact(
            'tenant',
            'reseller',
            'installations',
            'zones',
            'stats',
            'search',
            'zoneFilter',
            'perPage',
            'currencySymbol'
        ));
    }

    /**
     * Activate installation on field.
     */
    public function activateInstallation(Request $request, $id): JsonResponse|RedirectResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $customer = TenantCustomer::where('tenant_id', $tenant->id)
            ->where('reseller_id', $reseller->id)
            ->findOrFail($id);

        $request->validate([
            'onu_mac_sn' => 'nullable|string|max:50',
            'fiber_route_info' => 'nullable|string|max:255',
        ]);

        if ($request->filled('onu_mac_sn')) {
            $customer->onu_mac_sn = $request->onu_mac_sn;
        }
        if ($request->filled('fiber_route_info')) {
            $customer->fiber_route_info = $request->fiber_route_info;
        }

        $customer->status = 'active';
        $customer->online_status = 'online';
        $customer->save();

        $msg = "রিসেলার গ্রাহক '{$customer->username}' লাইন সফলভাবে চালু করা হয়েছে!";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'customer' => $customer,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Display Support Tickets for Reseller Tech.
     */
    public function tickets(Request $request): View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $tenantId = $tenant?->id;
        $resellerId = $reseller?->id;

        $search = $request->input('search');
        $statusFilter = $request->input('status', 'all');
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }

        $query = SupportTicket::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->with(['customer']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        if ($statusFilter && $statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        $tickets = $query->latest('id')->paginate($perPage)->withQueryString();

        $baseQuery = SupportTicket::where('tenant_id', $tenantId)->where('reseller_id', $resellerId);
        $stats = [
            'open_tickets' => (clone $baseQuery)->where('status', 'open')->count(),
            'in_progress_tickets' => (clone $baseQuery)->where('status', 'in_progress')->count(),
            'resolved_today' => (clone $baseQuery)->where('status', 'resolved')->whereDate('updated_at', today())->count(),
            'critical_priority' => (clone $baseQuery)->whereIn('priority', ['critical', 'high'])->where('status', '!=', 'resolved')->count(),
            'total_resolved' => (clone $baseQuery)->where('status', 'resolved')->count(),
            'total_tickets' => (clone $baseQuery)->count(),
        ];

        $currencySymbol = '৳';

        return view('reseller.tech.tickets', compact(
            'tenant',
            'reseller',
            'tickets',
            'stats',
            'search',
            'statusFilter',
            'perPage',
            'currencySymbol'
        ));
    }

    /**
     * Update ticket status.
     */
    public function updateTicketStatus(Request $request, $id): JsonResponse|RedirectResponse
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $ticket = SupportTicket::where('tenant_id', $tenant->id)
            ->where('reseller_id', $reseller->id)
            ->findOrFail($id);

        $request->validate([
            'status' => 'required|string|in:open,in_progress,answered,resolved,closed',
        ]);

        $ticket->status = $request->status;
        if ($request->status === 'resolved' || $request->status === 'closed') {
            $ticket->resolved_at = now();
        }
        $ticket->save();

        $msg = "টিকিট #{$ticket->ticket_number} রেজোলিউশন আপডেট সম্পন্ন হয়েছে।";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'ticket' => $ticket,
            ]);
        }

        return back()->with('success', $msg);
    }
}
