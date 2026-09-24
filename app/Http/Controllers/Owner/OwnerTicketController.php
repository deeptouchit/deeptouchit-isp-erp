<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\User;
use App\Notifications\TicketReplyNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class OwnerTicketController extends Controller
{
    public function index(Request $request)
    {
        $query = SupportTicket::with(['tenant', 'user', 'latestMessage'])->latest('updated_at');

        // Stats calculation
        $stats = [
            'total' => SupportTicket::count(),
            'open' => SupportTicket::whereIn('status', ['open', 'in_progress'])->count(),
            'awaiting_reply' => SupportTicket::whereIn('status', ['open', 'in_progress'])
                ->where('last_reply_by', 'tenant')
                ->count(),
            'urgent' => SupportTicket::where('priority', 'urgent')
                ->whereIn('status', ['open', 'in_progress', 'answered'])
                ->count(),
            'resolved' => SupportTicket::whereIn('status', ['resolved', 'closed'])->count(),
        ];

        // Search Filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhereHas('tenant', function ($t) use ($search) {
                      $t->where('company_name', 'like', "%{$search}%")
                        ->orWhere('domain', 'like', "%{$search}%");
                  });
            });
        }

        // Status Filter
        if ($status = $request->input('status')) {
            if ($status === 'open') {
                $query->whereIn('status', ['open', 'in_progress', 'answered']);
            } elseif ($status === 'pending_reply') {
                $query->whereIn('status', ['open', 'in_progress'])->where('last_reply_by', 'tenant');
            } else {
                $query->where('status', $status);
            }
        }

        // Priority Filter
        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        // Department Filter
        if ($dept = $request->input('department')) {
            $query->where('department', $dept);
        }

        // Tenant Filter
        if ($tenantId = $request->input('tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        $tickets = $query->paginate(15)->withQueryString();
        $tenants = Tenant::orderBy('company_name')->select('id', 'company_name', 'domain')->get();

        return view('owner.tickets.index', compact('tickets', 'stats', 'tenants'));
    }

    public function show(SupportTicket $ticket)
    {
        $ticket->load([
            'tenant.plan',
            'tenant.activeSubscription',
            'tenant.users',
            'user',
            'messages.user',
            'messages.attachments'
        ]);

        return view('owner.tickets.show', compact('ticket'));
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $request->validate([
            'message' => 'required|string',
            'is_internal_note' => 'nullable|boolean',
            'attachments.*' => 'nullable|file|max:10240', // 10MB max
        ]);

        $isInternal = $request->boolean('is_internal_note');

        DB::beginTransaction();
        try {
            $message = TicketMessage::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => auth()->id(),
                'sender_type' => 'owner',
                'message' => $request->message,
                'is_internal_note' => $isInternal,
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

            // Update ticket status & timestamps if it's a public reply
            if (!$isInternal) {
                $ticket->update([
                    'status' => 'answered',
                    'last_reply_by' => 'owner',
                    'last_reply_at' => now(),
                ]);

                // Notify Tenant ISP Admins
                $tenantUsers = User::where('tenant_id', $ticket->tenant_id)
                    ->whereIn('role', ['isp_admin', 'admin', 'tenant_owner'])
                    ->get();

                if ($tenantUsers->isNotEmpty()) {
                    Notification::send($tenantUsers, new TicketReplyNotification($ticket, $message, 'tenant'));
                }
            }

            DB::commit();

            $msgText = $isInternal ? 'Internal note added successfully.' : 'Reply sent to tenant successfully.';
            return back()->with('success', $msgText);
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to submit response: ' . $e->getMessage());
        }
    }

    public function updateStatus(Request $request, SupportTicket $ticket)
    {
        $request->validate([
            'status' => 'required|in:open,in_progress,answered,resolved,closed',
        ]);

        $data = ['status' => $request->status];
        if ($request->status === 'resolved') {
            $data['resolved_at'] = now();
        } elseif ($request->status === 'closed') {
            $data['closed_at'] = now();
        }

        $ticket->update($data);

        return back()->with('success', "Ticket status updated to " . ucfirst(str_replace('_', ' ', $request->status)));
    }

    public function updatePriority(Request $request, SupportTicket $ticket)
    {
        $request->validate([
            'priority' => 'required|in:low,medium,high,urgent',
        ]);

        $ticket->update(['priority' => $request->priority]);

        return back()->with('success', "Priority updated to " . ucfirst($request->priority));
    }

    public function destroy(SupportTicket $ticket)
    {
        $ticket->delete();
        return redirect()->route('owner.tickets.index')->with('success', "Ticket #{$ticket->ticket_number} deleted successfully.");
    }
}
