<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\TenantActivityLog;
use App\Models\TenantReseller;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\User;
use App\Notifications\TicketActivityNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\NotificationScopeService;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResellerTicketController extends Controller
{
    /**
     * Resolve the active authenticated Reseller and Tenant.
     */
    protected function getResellerData(): array
    {
        $user = Auth::user();
        $reseller = $user->reseller;
        abort_unless($reseller && $user->tenant_id && (int) $reseller->tenant_id === (int) $user->tenant_id, 403);

        return [$user, $reseller, $reseller->tenant];
    }

    private const DEPARTMENTS = [
        'technical' => 'Technical / NOC', 'billing' => 'Billing & Wallet', 'network' => 'Network & Router',
        'package' => 'Package & Speed', 'general' => 'General Support', 'sms_gateway' => 'SMS Gateway',
        'payment_gateway' => 'Payment Gateway',
    ];

    private function scopedTickets(TenantReseller $reseller): Builder
    {
        return SupportTicket::where('tenant_id', $reseller->tenant_id)->where('reseller_id', $reseller->id)
            ->when(Auth::user()->isResellerCollector(), fn ($query) => $query->where('user_id', Auth::id()));
    }

    private function filters(Request $request): array
    {
        $search = $request->query('search');
        $department = $request->query('department');
        $priority = $request->query('priority');
        $status = $request->query('status');

        return [
            'search' => is_string($search) ? mb_substr(trim($search), 0, 255) : '',
            'department' => is_string($department) && isset(self::DEPARTMENTS[$department]) ? $department : 'all',
            'priority' => in_array($priority, ['low', 'medium', 'high', 'urgent'], true) ? $priority : 'all',
            'status' => in_array($status, ['open', 'in_progress', 'answered', 'resolved', 'closed'], true) ? $status : 'all',
        ];
    }

    private function filteredTickets(TenantReseller $reseller, array $filters): Builder
    {
        ['search' => $search, 'department' => $department, 'priority' => $priority, 'status' => $status] = $filters;

        return $this->scopedTickets($reseller)
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('ticket_number', 'like', "%{$search}%")->orWhere('subject', 'like', "%{$search}%")))
            ->when($department !== 'all', fn ($query) => $query->where('department', $department))
            ->when($priority !== 'all', fn ($query) => $query->where('priority', $priority))
            ->when($status !== 'all', fn ($query) => match ($status) {
                'open' => $query->open(),
                'closed' => $query->closed(),
                default => $query->where('status', $status),
            })
            ->latest('updated_at')->latest('id');
    }

    /**
     * Display All Support Tickets for this Reseller.
     */
    public function index(Request $request): View
    {
        [$user, $reseller, $tenant] = $this->getResellerData();
        $filters = $this->filters($request);
        $perPage = $request->integer('per_page', 20);
        $pageSizes = [10, 15, 20, 30, 50, 100, 250, 500];
        if (!in_array($perPage, $pageSizes, true)) {
            $perPage = 20;
        }
        $departments = self::DEPARTMENTS;
        $reportQuery = array_filter($filters, fn ($value) => $value !== '' && $value !== 'all');
        $tickets = $this->filteredTickets($reseller, $filters)->paginate($perPage)->withQueryString();
        $baseCountQuery = $this->scopedTickets($reseller);

        $totalTickets = (clone $baseCountQuery)->count();
        $openTickets = (clone $baseCountQuery)->where('status', 'open')->count();
        $inProgressTickets = (clone $baseCountQuery)->where('status', 'in_progress')->count();
        $answeredTickets = (clone $baseCountQuery)->where('status', 'answered')->count();
        $urgentTickets = (clone $baseCountQuery)->whereIn('priority', ['urgent', 'high'])->whereNotIn('status', ['resolved', 'closed'])->count();
        $closedTickets = (clone $baseCountQuery)->whereIn('status', ['resolved', 'closed'])->count();

        $stats = [
            'total' => $totalTickets,
            'open' => $openTickets,
            'in_progress' => $inProgressTickets,
            'answered' => $answeredTickets,
            'urgent' => $urgentTickets,
            'closed' => $closedTickets,
        ];

        return view('reseller.tickets.index', compact(
            'user',
            'reseller',
            'tenant',
            'tickets',
            'stats',
            'departments',
            'pageSizes',
            'reportQuery',
            'perPage'
        ) + $filters);
    }

    /**
     * Store a newly created Support Ticket from Reseller to Host ISP.
     */
    public function store(Request $request): RedirectResponse
    {
        [$user, $reseller, $tenant] = $this->getResellerData();

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'department' => ['required', 'string', 'in:technical,billing,network,package,general'],
            'priority' => ['required', 'string', 'in:low,medium,high,urgent'],
            'message' => ['required', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf,txt,zip', 'max:5120'], // 5MB
        ]);

        $storedPath = null;
        DB::beginTransaction();
        try {
            // Generate unique ticket number
            $ticketNumber = 'TIC-' . strtoupper(Str::random(12));

            $ticket = SupportTicket::create([
                'ticket_number' => $ticketNumber,
                'tenant_id' => $tenant->id,
                'reseller_id' => $reseller?->id,
                'user_id' => $user->id,
                'department' => $validated['department'],
                'priority' => $validated['priority'],
                'status' => 'open',
                'subject' => $validated['subject'],
                'last_reply_by' => 'tenant',
                'last_reply_at' => now(),
            ]);

            $message = TicketMessage::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'sender_type' => 'tenant',
                'message' => $validated['message'],
                'is_internal_note' => false,
            ]);

            if ($request->hasFile('attachment')) {
                $storedPath = $this->storeAttachment($request->file('attachment'), $ticket);
                $message->attachments()->create([
                    'file_name' => $request->file('attachment')->getClientOriginalName(),
                    'file_path' => $storedPath,
                    'file_type' => $request->file('attachment')->getMimeType(),
                    'file_size' => $request->file('attachment')->getSize(),
                ]);
            }

            // Log activity
            try {
                TenantActivityLog::create([
                    'tenant_id' => $tenant->id,
                    'actor_type' => 'reseller',
                    'actor_id' => $user->id,
                    'actor_name' => $user->name ?? ($reseller?->name ?? 'Reseller Partner'),
                    'event_type' => 'ticket_created',
                    'description' => "Reseller '{$reseller?->name}' created support ticket #{$ticketNumber}: {$ticket->subject}",
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            } catch (\Throwable $e) {
                // Ignore activity log failure
            }

            // Dispatch real-time notification to Tenant Admins & Support Staff
            $tenantAdmins = User::where('tenant_id', $tenant->id)
                ->where(function ($q) {
                    $q->whereNull('reseller_id')->orWhere('reseller_id', 0);
                })
                ->get();
            $tenantAdmins = app(NotificationScopeService::class)
                ->filterRecipients($tenantAdmins, 'ticket_activity');

            if ($tenantAdmins->isNotEmpty()) {
                try {
                    Notification::send($tenantAdmins, new TicketActivityNotification(
                        $ticket,
                        "New Ticket #{$ticketNumber}",
                        "Partner '{$reseller?->name}' created ticket: {$ticket->subject}",
                        'ticket_created',
                        $user->name ?? 'Reseller Partner'
                    ));
                } catch (\Throwable $ne) {
                    // Fail gracefully without breaking transaction
                }
            }

            DB::commit();

            return redirect()->route('reseller.tickets.show', $ticket->id)->with('success', __('Support ticket created.'));
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($storedPath) {
                Storage::disk('local')->delete($storedPath);
            }
            if ($e instanceof ValidationException || $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                throw $e;
            }
            report($e);

            return back()->withInput()->with('error', __('Unable to save the support ticket.'));
        }
    }

    /**
     * Display Ticket Details & Conversation Thread.
     */
    public function show(int $id): View
    {
        [$user, $reseller, $tenant] = $this->getResellerData();

        $ticket = $this->scopedTickets($reseller)
            ->with(['publicMessages.user', 'publicMessages.attachments'])
            ->findOrFail($id);

        return view('reseller.tickets.show', compact('user', 'reseller', 'tenant', 'ticket') + ['departments' => self::DEPARTMENTS]);
    }

    /**
     * Reply to an Existing Ticket.
     */
    public function reply(Request $request, int $id): RedirectResponse
    {
        [$user, $reseller, $tenant] = $this->getResellerData();

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf,txt,zip', 'max:5120'],
        ]);

        $storedPath = null;
        DB::beginTransaction();
        try {
            $ticket = $this->scopedTickets($reseller)->lockForUpdate()->findOrFail($id);
            if (!$ticket->isOpen()) {
                throw ValidationException::withMessages(['message' => __('This ticket is already closed.')]);
            }

            $message = TicketMessage::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'sender_type' => 'tenant',
                'message' => $validated['message'],
                'is_internal_note' => false,
            ]);

            if ($request->hasFile('attachment')) {
                $storedPath = $this->storeAttachment($request->file('attachment'), $ticket);
                $message->attachments()->create([
                    'file_name' => $request->file('attachment')->getClientOriginalName(),
                    'file_path' => $storedPath,
                    'file_type' => $request->file('attachment')->getMimeType(),
                    'file_size' => $request->file('attachment')->getSize(),
                ]);
            }

            // Update ticket status & timestamps
            $ticket->update([
                'status' => 'open',
                'last_reply_by' => 'tenant',
                'last_reply_at' => now(),
            ]);

            // Dispatch notification to Tenant Admins
            $tenantAdmins = User::where('tenant_id', $tenant->id)
                ->where(function ($q) {
                    $q->whereNull('reseller_id')->orWhere('reseller_id', 0);
                })
                ->get();
            $tenantAdmins = app(NotificationScopeService::class)
                ->filterRecipients($tenantAdmins, 'ticket_status');

            if ($tenantAdmins->isNotEmpty()) {
                try {
                    Notification::send($tenantAdmins, new TicketActivityNotification(
                        $ticket,
                        "Reply on Ticket #{$ticket->ticket_number}",
                        "Partner '{$reseller?->name}' replied: " . Str::limit($validated['message'], 100),
                        'ticket_reply',
                        $user->name ?? 'Reseller Partner'
                    ));
                } catch (\Throwable $ne) {
                    // Fail gracefully
                }
            }

            DB::commit();

            return back()->with('success', __('Reply sent.'));
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($storedPath) {
                Storage::disk('local')->delete($storedPath);
            }
            if ($e instanceof ValidationException || $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                throw $e;
            }
            report($e);

            return back()->withInput()->with('error', __('Unable to save the support ticket.'));
        }
    }

    /**
     * Close / Resolve a Support Ticket.
     */
    public function closeTicket(Request $request, int $id)
    {
        [$user, $reseller, $tenant] = $this->getResellerData();

        $ticket = $this->scopedTickets($reseller)
            ->findOrFail($id);

        $changed = $this->scopedTickets($reseller)->whereKey($ticket->id)->open()->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        if (!$changed) {
            return $request->expectsJson()
                ? response()->json(['success' => true, 'message' => __('This ticket is already closed.')])
                : back()->with('success', __('This ticket is already closed.'));
        }

        // Dispatch notification to Tenant Admins
        $tenantAdmins = User::where('tenant_id', $tenant->id)
            ->where(function ($q) {
                $q->whereNull('reseller_id')->orWhere('reseller_id', 0);
            })
            ->get();
        $tenantAdmins = app(NotificationScopeService::class)->filterRecipients($tenantAdmins, 'ticket_status');

        if ($tenantAdmins->isNotEmpty()) {
            try {
                Notification::send($tenantAdmins, new TicketActivityNotification(
                    $ticket,
                    "Ticket #{$ticket->ticket_number} Closed",
                    "Partner '{$reseller?->name}' marked ticket as closed.",
                    'ticket_closed',
                    $user->name ?? 'Reseller Partner'
                ));
            } catch (\Throwable $ne) {
                // Fail gracefully
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Ticket closed.'),
            ]);
        }

        return back()->with('success', __('Ticket closed.'));
    }

    /**
     * Export Tickets as CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        [$user, $reseller, $tenant] = $this->getResellerData();
        $fileName = 'reseller_tickets_' . now()->format('Ymd_His') . '.csv';
        $tickets = $this->filteredTickets($reseller, $this->filters($request))->cursor();

        return response()->streamDownload(function () use ($tickets) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['#', 'Ticket ID', 'Subject', 'Department', 'Priority', 'Status', 'Last Reply', 'Created At'], escape: '');

            foreach ($tickets as $index => $t) {
                $subject = preg_match('/^(?:\s*[=+@-]|[\t\r\n])/', $t->subject) ? "'" . $t->subject : $t->subject;
                fputcsv($handle, [
                    $index + 1,
                    $t->ticket_number,
                    $subject,
                    ucfirst($t->department),
                    strtoupper($t->priority),
                    strtoupper($t->status),
                    $t->last_reply_at ? $t->last_reply_at->format('Y-m-d H:i') : 'N/A',
                    $t->created_at->format('Y-m-d H:i'),
                ], escape: '');
            }
            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Print Tickets Report.
     */
    public function printReport(Request $request): View
    {
        [$user, $reseller, $tenant] = $this->getResellerData();
        $tickets = $this->filteredTickets($reseller, $this->filters($request))->get();

        return view('reseller.tickets.print', compact('user', 'reseller', 'tenant', 'tickets') + ['departments' => self::DEPARTMENTS]);
    }

    public function downloadAttachment(int $id, int $attachment): StreamedResponse
    {
        [, $reseller] = $this->getResellerData();
        $ticket = $this->scopedTickets($reseller)->findOrFail($id);
        $file = TicketAttachment::whereHas('message', fn ($query) => $query
            ->where('support_ticket_id', $ticket->id)->where('is_internal_note', false))->findOrFail($attachment);

        return $file->download();
    }

    private function storeAttachment(UploadedFile $file, SupportTicket $ticket): string
    {
        $path = $file->store('private-ticket-attachments/' . $ticket->id, 'local');
        if (!$path) {
            throw ValidationException::withMessages(['attachment' => __('Unable to save the support ticket.')]);
        }

        return $path;
    }

    /**
     * Get recent support ticket messages/conversations for topbar message dropdown (JSON API).
     */
    public function getTopbarMessages(Request $request): JsonResponse
    {
        [$user, $reseller, $tenant] = $this->getResellerData();
        $ticketsQuery = $this->scopedTickets($reseller);

        // Count of tickets that are answered by host ISP (requiring reseller attention)
        $answeredCount = (clone $ticketsQuery)->where('status', 'answered')->count();
        $totalOpen = (clone $ticketsQuery)->whereIn('status', ['open', 'in_progress', 'answered'])->count();

        // Get latest active tickets with their latest message
        $recentTickets = (clone $ticketsQuery)
            ->with(['latestPublicMessage.user'])
            ->latest('updated_at')
            ->take(8)
            ->get()
            ->map(function ($ticket) use ($reseller) {
                $latestMsg = $ticket->latestPublicMessage;
                $isFromHost = $latestMsg ? ($latestMsg->sender_type !== 'reseller' && (int) $latestMsg->user?->reseller_id !== (int) $reseller->id) : false;
                
                // Excerpt
                $excerpt = $latestMsg ? Str::limit(strip_tags($latestMsg->message), 70) : __('No messages yet');
                $senderName = $latestMsg?->user?->name ?? ($isFromHost ? __('ISP Support') : __('Reseller'));

                return [
                    'id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'subject' => $ticket->subject,
                    'department' => ucfirst($ticket->department),
                    'priority' => strtoupper($ticket->priority),
                    'status' => $ticket->status,
                    'is_answered' => $ticket->status === 'answered',
                    'last_message' => $excerpt,
                    'sender_name' => $senderName,
                    'is_from_host' => $isFromHost,
                    'time_ago' => $ticket->updated_at ? $ticket->updated_at->locale(app()->getLocale())->diffForHumans() : '',
                    'url' => route('reseller.tickets.show', $ticket->id),
                ];
            });

        return response()->json([
            'unread_count' => $answeredCount,
            'total_open' => $totalOpen,
            'tickets' => $recentTickets,
        ]);
    }
}
