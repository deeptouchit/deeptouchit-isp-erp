<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\SupportTicket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\User;
use App\Notifications\NewTicketOwnerNotification;
use App\Notifications\TicketReplyNotification;
use App\Notifications\TicketActivityNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TenantTicketController extends Controller
{
    /**
     * Get Current Active Tenant Workspace
     */
    protected function getTenant(): Tenant
    {
        $tenantId = session('tenant_id') ?? auth()->user()?->tenant_id;
        $tenant = $tenantId ? Tenant::find($tenantId) : (auth()->user()?->tenant ?? Tenant::first());

        if (!$tenant) {
            abort(403, 'Tenant context not found. Please log in.');
        }

        return $tenant;
    }

    /**
     * Display All Support Tickets List
     */
    public function index(Request $request): View
    {
        $tenant = $this->getTenant();
        $tenantId = $tenant->id;
        $authUser = auth()->user();

        $department = $request->get('department', 'all');
        $priority = $request->get('priority', 'all');
        $status = $request->get('status', 'all');
        $search = trim($request->get('search', ''));
        $perPage = (int) $request->get('per_page', 20);

        // 1. Build Filtered Query
        $query = SupportTicket::where('tenant_id', $tenantId)->latest('updated_at');

        // Scope strictly to collector's own tickets
        if ($authUser && ($authUser->isCollector() || $authUser->isTechnician())) {
            $query->where('user_id', $authUser->id);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        if ($department !== 'all' && !empty($department)) {
            $query->where('department', $department);
        }

        if ($priority !== 'all' && !empty($priority)) {
            $query->where('priority', $priority);
        }

        if ($status !== 'all' && !empty($status)) {
            if ($status === 'open') {
                $query->whereIn('status', ['open', 'in_progress', 'answered']);
            } elseif ($status === 'closed') {
                $query->whereIn('status', ['resolved', 'closed']);
            } else {
                $query->where('status', $status);
            }
        }

        $tickets = $query->paginate($perPage)->withQueryString();

        // 2. Compute 6 KPI Summary Cards
        $baseKpiQuery = SupportTicket::where('tenant_id', $tenantId);
        if ($authUser && ($authUser->isCollector() || $authUser->isTechnician())) {
            $baseKpiQuery->where('user_id', $authUser->id);
        }

        $totalTickets = (clone $baseKpiQuery)->count();
        $openTickets = (clone $baseKpiQuery)->where('status', 'open')->count();
        $inProgressTickets = (clone $baseKpiQuery)->where('status', 'in_progress')->count();
        $answeredTickets = (clone $baseKpiQuery)->where('status', 'answered')->count();
        $urgentTickets = (clone $baseKpiQuery)->whereIn('priority', ['urgent', 'high'])->whereNotIn('status', ['resolved', 'closed'])->count();
        $closedTickets = (clone $baseKpiQuery)->whereIn('status', ['resolved', 'closed'])->count();

        $stats = [
            'total' => $totalTickets,
            'open' => $openTickets,
            'in_progress' => $inProgressTickets,
            'answered' => $answeredTickets,
            'urgent' => $urgentTickets,
            'closed' => $closedTickets,
        ];

        return view('tenant.tickets.index', compact(
            'tenant',
            'tickets',
            'stats',
            'department',
            'priority',
            'status',
            'search',
            'perPage'
        ));
    }

    /**
     * Show Create New Ticket Form
     */
    public function create(): View
    {
        $tenant = $this->getTenant();
        return view('tenant.tickets.create', compact('tenant'));
    }

    /**
     * Store Newly Created Ticket
     */
    public function store(Request $request)
    {
        $tenant = $this->getTenant();
        $tenantId = $tenant->id;

        $request->validate([
            'subject' => 'required|string|max:255',
            'department' => 'required|string|in:general,technical,billing,sms_gateway,payment_gateway',
            'priority' => 'required|in:low,medium,high,urgent',
            'message' => 'required|string',
            'attachments.*' => 'nullable|file|max:10240', // 10MB max
        ]);

        DB::beginTransaction();
        try {
            // Generate unique ticket number e.g. TKT-26-10492
            $ticketNumber = 'TKT-' . date('y') . '-' . strtoupper(Str::random(5));
            while (SupportTicket::where('ticket_number', $ticketNumber)->exists()) {
                $ticketNumber = 'TKT-' . date('y') . '-' . strtoupper(Str::random(5));
            }

            $ticket = SupportTicket::create([
                'ticket_number' => $ticketNumber,
                'tenant_id' => $tenantId,
                'user_id' => auth()->id() ?? 1,
                'department' => $request->department,
                'priority' => $request->priority,
                'status' => 'open',
                'subject' => $request->subject,
                'last_reply_by' => 'tenant',
                'last_reply_at' => now(),
            ]);

            $message = TicketMessage::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => auth()->id() ?? 1,
                'sender_type' => 'tenant',
                'message' => $request->message,
                'is_internal_note' => false,
            ]);

            // Handle Attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store("tickets/{$ticket->id}", 'public');
                    TicketAttachment::create([
                        'ticket_message_id' => $message->id,
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_type' => $file->getClientOriginalExtension(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            // Dispatch notification to Owner / Super Admins
            $superAdmins = User::whereIn('role', ['owner', 'super_admin'])->get();
            if ($superAdmins->isNotEmpty()) {
                try {
                    Notification::send($superAdmins, new NewTicketOwnerNotification($ticket));
                } catch (\Throwable $e) {
                    // Ignore mailer transport issues
                }
            }

            DB::commit();

            return redirect()->route('tenant.tickets.show', $ticket->id)->with('success', "Support ticket {$ticketNumber} has been created successfully. Our team will review and reply shortly.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create ticket: ' . $e->getMessage());
        }
    }

    /**
     * Show Support Ticket Conversation
     */
    public function show(SupportTicket $ticket): View
    {
        $tenant = $this->getTenant();
        $tenantId = $tenant->id;
        $authUser = auth()->user();

        // Security check
        if ($ticket->tenant_id != $tenantId && !$authUser?->isOwner()) {
            abort(403, 'Unauthorized access to this ticket.');
        }

        if ($authUser && ($authUser->isCollector() || $authUser->isTechnician()) && $ticket->user_id !== $authUser->id) {
            abort(403, 'You can only access support tickets opened by you.');
        }

        $ticket->load([
            'user',
            'publicMessages.user',
            'publicMessages.attachments'
        ]);

        return view('tenant.tickets.show', compact('tenant', 'ticket'));
    }

    /**
     * Reply to an Existing Support Ticket
     */
    public function reply(Request $request, SupportTicket $ticket)
    {
        $tenant = $this->getTenant();
        $tenantId = $tenant->id;
        $authUser = auth()->user();

        if ($ticket->tenant_id != $tenantId && !$authUser?->isOwner()) {
            abort(403, 'Unauthorized access to this ticket.');
        }

        if ($authUser && ($authUser->isCollector() || $authUser->isTechnician()) && $ticket->user_id !== $authUser->id) {
            abort(403, 'You can only access support tickets opened by you.');
        }

        $request->validate([
            'message' => 'required|string',
            'attachments.*' => 'nullable|file|max:10240',
        ]);

        DB::beginTransaction();
        try {
            $message = TicketMessage::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => auth()->id() ?? 1,
                'sender_type' => 'tenant',
                'message' => $request->message,
                'is_internal_note' => false,
            ]);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store("tickets/{$ticket->id}", 'public');
                    TicketAttachment::create([
                        'ticket_message_id' => $message->id,
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_type' => $file->getClientOriginalExtension(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            // Update ticket status to in_progress or open if resolved
            $newStatus = in_array($ticket->status, ['resolved', 'closed']) ? 'open' : 'in_progress';
            $ticket->update([
                'status' => $newStatus,
                'last_reply_by' => 'tenant',
                'last_reply_at' => now(),
            ]);

            // Notify relevant recipients
            if ($ticket->reseller_id || ($ticket->user && $ticket->user->isResellerUser())) {
                $resellerUsers = User::where(function ($q) use ($ticket) {
                    if ($ticket->reseller_id) {
                        $q->where('reseller_id', $ticket->reseller_id);
                    }
                    if ($ticket->user_id) {
                        $q->orWhere('id', $ticket->user_id);
                    }
                })->get();

                if ($resellerUsers->isNotEmpty()) {
                    try {
                        Notification::send($resellerUsers, new TicketActivityNotification(
                            $ticket,
                            "Reply on Ticket #{$ticket->ticket_number}",
                            "Support Desk replied: " . Str::limit($request->message, 100),
                            'ticket_reply',
                            auth()->user()?->name ?? 'Host ISP Support'
                        ));
                    } catch (\Throwable $ne) {
                        // Fail gracefully
                    }
                }
            } else {
                // Notify Super Admins if tenant-to-owner ticket
                $superAdmins = User::whereIn('role', ['owner', 'super_admin'])->get();
                if ($superAdmins->isNotEmpty()) {
                    try {
                        Notification::send($superAdmins, new TicketReplyNotification($ticket, $message, 'owner'));
                    } catch (\Throwable $e) {
                        // Ignore mailer transport issues
                    }
                }
            }

            DB::commit();

            return back()->with('success', 'Your reply has been submitted successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to send reply: ' . $e->getMessage());
        }
    }

    /**
     * Quick Close Ticket
     */
    public function closeTicket(Request $request, $id)
    {
        $tenant = $this->getTenant();
        $authUser = auth()->user();
        $ticket = SupportTicket::where('tenant_id', $tenant->id)->findOrFail($id);

        if ($authUser && ($authUser->isCollector() || $authUser->isTechnician()) && $ticket->user_id !== $authUser->id) {
            abort(403, 'You can only close support tickets opened by you.');
        }

        $ticket->status = 'closed';
        $ticket->closed_at = now();
        $ticket->save();

        // Dispatch notification if reseller ticket
        if ($ticket->reseller_id || ($ticket->user && $ticket->user->isResellerUser())) {
            $resellerUsers = User::where(function ($q) use ($ticket) {
                if ($ticket->reseller_id) {
                    $q->where('reseller_id', $ticket->reseller_id);
                }
                if ($ticket->user_id) {
                    $q->orWhere('id', $ticket->user_id);
                }
            })->get();

            if ($resellerUsers->isNotEmpty()) {
                try {
                    Notification::send($resellerUsers, new TicketActivityNotification(
                        $ticket,
                        "Ticket #{$ticket->ticket_number} Closed",
                        "Support Desk marked ticket '{$ticket->subject}' as closed.",
                        'ticket_closed',
                        auth()->user()?->name ?? 'Host ISP Support'
                    ));
                } catch (\Throwable $ne) {
                    // Fail gracefully
                }
            }
        }

        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json([
                'success' => true,
                'message' => "Ticket {$ticket->ticket_number} has been closed.",
            ]);
        }

        return back()->with('success', "Ticket {$ticket->ticket_number} has been closed.");
    }

    /**
     * Quick Reopen Ticket
     */
    public function reopenTicket(Request $request, $id)
    {
        $tenant = $this->getTenant();
        $authUser = auth()->user();
        $ticket = SupportTicket::where('tenant_id', $tenant->id)->findOrFail($id);

        if ($authUser && ($authUser->isCollector() || $authUser->isTechnician()) && $ticket->user_id !== $authUser->id) {
            abort(403, 'You can only reopen support tickets opened by you.');
        }

        $ticket->status = 'open';
        $ticket->closed_at = null;
        $ticket->save();

        // Dispatch notification if reseller ticket
        if ($ticket->reseller_id || ($ticket->user && $ticket->user->isResellerUser())) {
            $resellerUsers = User::where(function ($q) use ($ticket) {
                if ($ticket->reseller_id) {
                    $q->where('reseller_id', $ticket->reseller_id);
                }
                if ($ticket->user_id) {
                    $q->orWhere('id', $ticket->user_id);
                }
            })->get();

            if ($resellerUsers->isNotEmpty()) {
                try {
                    Notification::send($resellerUsers, new TicketActivityNotification(
                        $ticket,
                        "Ticket #{$ticket->ticket_number} Reopened",
                        "Support Desk reopened ticket '{$ticket->subject}'.",
                        'ticket_reopened',
                        auth()->user()?->name ?? 'Host ISP Support'
                    ));
                } catch (\Throwable $ne) {
                    // Fail gracefully
                }
            }
        }

        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json([
                'success' => true,
                'message' => "Ticket {$ticket->ticket_number} has been reopened.",
            ]);
        }

        return back()->with('success', "Ticket {$ticket->ticket_number} has been reopened.");
    }
}
