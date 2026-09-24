<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Models\TenantActivityLog;
use App\Models\TenantReseller;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\User;
use App\Notifications\TicketActivityNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
     * Display All Support Tickets for this Reseller.
     */
    public function index(Request $request): View
    {
        [$user, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        $search = trim($request->input('search', ''));
        $department = $request->input('department', 'all');
        $priority = $request->input('priority', 'all');
        $status = $request->input('status', 'all');
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }

        // Base Query scoped to this reseller
        $query = SupportTicket::where('tenant_id', $tenantId)
            ->where(function ($q) use ($resellerId, $user) {
                if ($resellerId) {
                    $q->where('reseller_id', $resellerId);
                } else {
                    $q->where('user_id', $user->id);
                }
            })
            ->latest('updated_at');

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

        // 6 Summary Metric Cards (AGENTS.md Rule 2.B)
        $baseCountQuery = SupportTicket::where('tenant_id', $tenantId)
            ->where(function ($q) use ($resellerId, $user) {
                if ($resellerId) {
                    $q->where('reseller_id', $resellerId);
                } else {
                    $q->where('user_id', $user->id);
                }
            });

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
            'search',
            'department',
            'priority',
            'status',
            'perPage'
        ));
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
            'attachment' => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf,txt,log,zip', 'max:5120'], // 5MB
        ]);

        DB::beginTransaction();
        try {
            // Generate unique ticket number
            $ticketNumber = 'TIC-' . strtoupper(Str::random(3)) . '-' . rand(1000, 9999);

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

            // Handle Attachment
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '-' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('ticket_attachments/' . $ticket->id, $filename, 'public');

                TicketAttachment::create([
                    'ticket_message_id' => $message->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
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

            return redirect()->route('reseller.tickets.show', $ticket->id)->with('success', "সাপোর্ট টিকিট (#{$ticketNumber}) সফলভাবে তৈরি করা হয়েছে।");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'টিকিট তৈরি করতে সমস্যা হয়েছে: ' . $e->getMessage());
        }
    }

    /**
     * Display Ticket Details & Conversation Thread.
     */
    public function show(int $id): View
    {
        [$user, $reseller, $tenant] = $this->getResellerData();

        $ticket = SupportTicket::where('tenant_id', $tenant->id)
            ->where(function ($q) use ($reseller, $user) {
                if ($reseller) {
                    $q->where('reseller_id', $reseller->id);
                } else {
                    $q->where('user_id', $user->id);
                }
            })
            ->with(['messages.user', 'messages.attachments'])
            ->findOrFail($id);

        return view('reseller.tickets.show', compact('user', 'reseller', 'tenant', 'ticket'));
    }

    /**
     * Reply to an Existing Ticket.
     */
    public function reply(Request $request, int $id): RedirectResponse
    {
        [$user, $reseller, $tenant] = $this->getResellerData();

        $ticket = SupportTicket::where('tenant_id', $tenant->id)
            ->where(function ($q) use ($reseller, $user) {
                if ($reseller) {
                    $q->where('reseller_id', $reseller->id);
                } else {
                    $q->where('user_id', $user->id);
                }
            })
            ->findOrFail($id);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf,txt,log,zip', 'max:5120'],
        ]);

        DB::beginTransaction();
        try {
            $message = TicketMessage::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'sender_type' => 'tenant',
                'message' => $validated['message'],
                'is_internal_note' => false,
            ]);

            // Handle Attachment
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '-' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('ticket_attachments/' . $ticket->id, $filename, 'public');

                TicketAttachment::create([
                    'ticket_message_id' => $message->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
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

            return back()->with('success', 'আপনার বার্তাটি সফলভাবে পাঠানো হয়েছে।');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'বার্তা পাঠাতে সমস্যা হয়েছে: ' . $e->getMessage());
        }
    }

    /**
     * Close / Resolve a Support Ticket.
     */
    public function closeTicket(Request $request, int $id)
    {
        [$user, $reseller, $tenant] = $this->getResellerData();

        $ticket = SupportTicket::where('tenant_id', $tenant->id)
            ->where(function ($q) use ($reseller, $user) {
                if ($reseller) {
                    $q->where('reseller_id', $reseller->id);
                } else {
                    $q->where('user_id', $user->id);
                }
            })
            ->findOrFail($id);

        $ticket->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        // Dispatch notification to Tenant Admins
        $tenantAdmins = User::where('tenant_id', $tenant->id)
            ->where(function ($q) {
                $q->whereNull('reseller_id')->orWhere('reseller_id', 0);
            })
            ->get();

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
                'message' => "Ticket #{$ticket->ticket_number} marked as closed.",
            ]);
        }

        return back()->with('success', "টিকিট #{$ticket->ticket_number} বন্ধ করা হয়েছে।");
    }

    /**
     * Export Tickets as CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        [$user, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        $fileName = 'reseller_tickets_' . date('Ymd_His') . '.csv';

        $tickets = SupportTicket::where('tenant_id', $tenantId)
            ->where(function ($q) use ($resellerId, $user) {
                if ($resellerId) {
                    $q->where('reseller_id', $resellerId);
                } else {
                    $q->where('user_id', $user->id);
                }
            })
            ->latest('id')
            ->get();

        return response()->streamDownload(function () use ($tickets) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['#', 'Ticket ID', 'Subject', 'Department', 'Priority', 'Status', 'Last Reply', 'Created At']);

            foreach ($tickets as $index => $t) {
                fputcsv($handle, [
                    $index + 1,
                    $t->ticket_number,
                    $t->subject,
                    ucfirst($t->department),
                    strtoupper($t->priority),
                    strtoupper($t->status),
                    $t->last_reply_at ? $t->last_reply_at->format('Y-m-d H:i') : 'N/A',
                    $t->created_at->format('Y-m-d H:i'),
                ]);
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
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        $tickets = SupportTicket::where('tenant_id', $tenantId)
            ->where(function ($q) use ($resellerId, $user) {
                if ($resellerId) {
                    $q->where('reseller_id', $resellerId);
                } else {
                    $q->where('user_id', $user->id);
                }
            })
            ->latest('id')
            ->get();

        return view('reseller.tickets.print', compact('user', 'reseller', 'tenant', 'tickets'));
    }

    /**
     * Get recent support ticket messages/conversations for topbar message dropdown (JSON API).
     */
    public function getTopbarMessages(Request $request): JsonResponse
    {
        [$user, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        if (!$user) {
            return response()->json([
                'unread_count' => 0,
                'total_open' => 0,
                'tickets' => [],
            ]);
        }

        // Base tickets query
        $ticketsQuery = SupportTicket::where('tenant_id', $tenantId)
            ->where(function ($q) use ($resellerId, $user) {
                if ($resellerId) {
                    $q->where('reseller_id', $resellerId);
                } else {
                    $q->where('user_id', $user->id);
                }
            });

        // Count of tickets that are answered by host ISP (requiring reseller attention)
        $answeredCount = (clone $ticketsQuery)->where('status', 'answered')->count();
        $totalOpen = (clone $ticketsQuery)->whereIn('status', ['open', 'in_progress', 'answered'])->count();

        // Get latest active tickets with their latest message
        $recentTickets = (clone $ticketsQuery)
            ->with(['latestMessage.user'])
            ->latest('updated_at')
            ->take(8)
            ->get()
            ->map(function ($ticket) use ($user) {
                $latestMsg = $ticket->latestMessage;
                $isFromHost = $latestMsg ? ($latestMsg->user_id !== $user->id) : false;
                
                // Excerpt
                $excerpt = $latestMsg ? Str::limit(strip_tags($latestMsg->message), 70) : 'No messages yet';
                $senderName = $latestMsg?->user?->name ?? ($isFromHost ? 'Host ISP Support' : 'You');

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
                    'time_ago' => $ticket->updated_at ? $ticket->updated_at->locale('en')->diffForHumans() : '',
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
