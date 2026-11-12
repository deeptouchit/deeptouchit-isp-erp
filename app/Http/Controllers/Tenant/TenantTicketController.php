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
        $user = auth()->user();
        $tenant = $user?->tenant;
        if (!$tenant && $user?->tenant_id) {
            $tenant = Tenant::find($user->tenant_id);
        }
        $tenantId = session('tenant_id');
        if (!$tenant && $tenantId) {
            $tenant = Tenant::find($tenantId);
        }

        if (!$tenant) {
            abort(403, __('Tenant context not found. Please log in.'));
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

        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return view('tenant.tickets.index', compact(
            'tenant',
            'currencySymbol',
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
        $currencySymbol = $tenant->currency_symbol ?? '৳';
        return view('tenant.tickets.create', compact('tenant', 'currencySymbol'));
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
        ], [
            'subject.required' => __('Please enter a ticket subject.'),
            'subject.max' => __('Subject may not be greater than 255 characters.'),
            'department.required' => __('Please select a department.'),
            'department.in' => __('Selected department is invalid.'),
            'priority.required' => __('Please select a priority level.'),
            'priority.in' => __('Selected priority is invalid.'),
            'message.required' => __('Please enter a detailed message.'),
            'attachments.*.file' => __('Each attachment must be a valid file.'),
            'attachments.*.max' => __('Each attachment may not be larger than 10MB.'),
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

            return redirect()->route('tenant.tickets.show', $ticket->id)->with('success', __('Support ticket :number has been created successfully. Our team will review and reply shortly.', ['number' => $ticketNumber]));
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', __('Failed to create ticket: :error', ['error' => $e->getMessage()]));
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
            abort(403, __('Unauthorized access to this ticket.'));
        }

        if ($authUser && ($authUser->isCollector() || $authUser->isTechnician()) && $ticket->user_id !== $authUser->id) {
            abort(403, __('You can only access support tickets opened by you.'));
        }

        $ticket->load([
            'user',
            'publicMessages.user',
            'publicMessages.attachments'
        ]);

        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return view('tenant.tickets.show', compact('tenant', 'currencySymbol', 'ticket'));
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
            abort(403, __('Unauthorized access to this ticket.'));
        }

        if ($authUser && ($authUser->isCollector() || $authUser->isTechnician()) && $ticket->user_id !== $authUser->id) {
            abort(403, __('You can only access support tickets opened by you.'));
        }

        $request->validate([
            'message' => 'required|string',
            'attachments.*' => 'nullable|file|max:10240',
        ], [
            'message.required' => __('Please enter your reply message.'),
            'attachments.*.file' => __('Each attachment must be a valid file.'),
            'attachments.*.max' => __('Each attachment may not be larger than 10MB.'),
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
                            __('Reply on Ticket #:number', ['number' => $ticket->ticket_number]),
                            __('Support Desk replied: :message', ['message' => Str::limit($request->message, 100)]),
                            'ticket_reply',
                            auth()->user()?->name ?? __('Host ISP Support')
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

            return back()->with('success', __('Your reply has been submitted successfully.'));
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', __('Failed to send reply: :error', ['error' => $e->getMessage()]));
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
            abort(403, __('You can only close support tickets opened by you.'));
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
                        __('Ticket #:number Closed', ['number' => $ticket->ticket_number]),
                        __('Support Desk marked ticket ":subject" as closed.', ['subject' => $ticket->subject]),
                        'ticket_closed',
                        auth()->user()?->name ?? __('Host ISP Support')
                    ));
                } catch (\Throwable $ne) {
                    // Fail gracefully
                }
            }
        }

        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json([
                'success' => true,
                'message' => __('Ticket :number has been closed.', ['number' => $ticket->ticket_number]),
            ]);
        }

        return back()->with('success', __('Ticket :number has been closed.', ['number' => $ticket->ticket_number]));
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
            abort(403, __('You can only reopen support tickets opened by you.'));
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
                        __('Ticket #:number Reopened', ['number' => $ticket->ticket_number]),
                        __('Support Desk reopened ticket ":subject".', ['subject' => $ticket->subject]),
                        'ticket_reopened',
                        auth()->user()?->name ?? __('Host ISP Support')
                    ));
                } catch (\Throwable $ne) {
                    // Fail gracefully
                }
            }
        }

        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json([
                'success' => true,
                'message' => __('Ticket :number has been reopened.', ['number' => $ticket->ticket_number]),
            ]);
        }

        return back()->with('success', __('Ticket :number has been reopened.', ['number' => $ticket->ticket_number]));
    }

    /**
     * Delete Support Ticket (Super Admin / Owner only)
     */
    public function destroy(Request $request, $id)
    {
        $tenant = $this->getTenant();
        $authUser = auth()->user();

        // Strictly authorize Super Admin / ISP Admin
        if (!$authUser || !$authUser->canDeleteTickets()) {
            if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
                return response()->json([
                    'success' => false,
                    'message' => __('Unauthorized: Only Super Admin / ISP Admin can delete support tickets.'),
                ], 403);
            }
            abort(403, __('Unauthorized: Only Super Admin / ISP Admin can delete support tickets.'));
        }

        $ticket = SupportTicket::where('tenant_id', $tenant->id)->findOrFail($id);
        $ticketNumber = $ticket->ticket_number;

        DB::beginTransaction();
        try {
            // Delete associated attachment files from storage
            foreach ($ticket->messages as $msg) {
                foreach ($msg->attachments as $att) {
                    if ($att->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($att->file_path)) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($att->file_path);
                    }
                    $att->delete();
                }
                $msg->delete();
            }

            $ticket->delete();
            DB::commit();

            if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
                return response()->json([
                    'success' => true,
                    'message' => __('Ticket #:number deleted successfully.', ['number' => $ticketNumber]),
                ]);
            }

            return redirect()->route('tenant.tickets.index')->with('success', __('Ticket #:number deleted successfully.', ['number' => $ticketNumber]));
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to delete ticket: :error', ['error' => $e->getMessage()]),
                ], 500);
            }
            return back()->with('error', __('Failed to delete ticket: :error', ['error' => $e->getMessage()]));
        }
    }
}
