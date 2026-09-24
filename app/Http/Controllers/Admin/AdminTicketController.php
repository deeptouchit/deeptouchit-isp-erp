<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CannedResponse;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminTicketController extends Controller
{
    /**
     * Display helpdesk directory, ticket queues, and resolution telemetry.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = SupportTicket::with([
            'user:id,first_name,last_name,username,email',
            'assignedTo:id,first_name,last_name,username,email',
        ])->withCount('replies');

        // Search Filter
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_no', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Status Filter
        if ($status = $request->input('status')) {
            if ($status === 'open') {
                $query->whereIn('status', ['open', 'waiting']);
            } elseif ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        // Priority Filter
        if ($priority = $request->input('priority')) {
            if ($priority !== 'all') {
                $query->where('priority', $priority);
            }
        }

        // Department Filter
        if ($department = $request->input('department')) {
            if ($department !== 'all') {
                $query->where('department', $department);
            }
        }

        // Assigned Agent Filter
        if ($assigned = $request->input('assigned_to')) {
            if ($assigned === 'unassigned') {
                $query->whereNull('assigned_to');
            } elseif ($assigned !== 'all') {
                $query->where('assigned_to', $assigned);
            }
        }

        $tickets = $query->latest('updated_at')->paginate(15)->withQueryString();

        // Real-time Helpdesk Metric Counters
        $stats = [
            'total_tickets' => SupportTicket::count(),
            'open_count' => SupportTicket::whereIn('status', ['open', 'waiting'])->count(),
            'in_progress_count' => SupportTicket::where('status', 'in_progress')->count(),
            'answered_count' => SupportTicket::where('status', 'answered')->count(),
            'closed_count' => SupportTicket::where('status', 'closed')->count(),
            'critical_count' => SupportTicket::whereIn('status', ['open', 'waiting', 'in_progress'])->where('priority', 'critical')->count(),
        ];

        // Staff Agents list for assignment picker
        $staff = User::whereIn('role', ['admin', 'reseller'])
            ->select('id', 'first_name', 'last_name', 'username', 'email')
            ->orderBy('first_name')
            ->get();

        // Clients list for opening a ticket modal
        $clients = User::where('role', 'client')
            ->select('id', 'first_name', 'last_name', 'username', 'email')
            ->orderBy('first_name')
            ->get();

        return Inertia::render('Admin/Tickets/Index', [
            'tickets' => $tickets,
            'stats' => $stats,
            'staff' => $staff,
            'clients' => $clients,
            'filters' => $request->only(['search', 'status', 'priority', 'department', 'assigned_to']),
        ]);
    }

    /**
     * Store a new support ticket.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'subject' => ['required', 'string', 'max:255'],
            'department' => ['required', 'in:technical,billing,sales,abuse'],
            'priority' => ['required', 'in:low,medium,high,critical'],
            'message' => ['required', 'string'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $ticket = SupportTicket::create([
            'user_id' => $validated['user_id'],
            'subject' => $validated['subject'],
            'department' => $validated['department'],
            'priority' => $validated['priority'],
            'message' => $validated['message'],
            'assigned_to' => $validated['assigned_to'] ?? auth()->id(),
            'status' => 'open',
        ]);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'ticket_created',
            'description' => "Created support ticket #{$ticket->ticket_no}: '{$ticket->subject}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['ticket_no' => $ticket->ticket_no, 'subject' => $ticket->subject],
        ]);

        return redirect()->route('admin.tickets.show', $ticket->id)
            ->with('success', "Support ticket #{$ticket->ticket_no} created successfully.");
    }

    /**
     * Display ticket conversation thread and diagnostic workspace.
     */
    public function show(SupportTicket $ticket): Response
    {
        $this->authorize('viewAny', User::class);

        $ticket->load([
            'user' => function ($q) {
                $q->select('id', 'first_name', 'last_name', 'username', 'email', 'phone', 'created_at', 'status', 'credit_balance')
                    ->withCount('subscriptions');
            },
            'assignedTo:id,first_name,last_name,username,email',
            'replies.user:id,first_name,last_name,username,email,role',
        ]);

        // Staff Agents for assignment dropdown
        $staff = User::whereIn('role', ['admin', 'reseller'])
            ->select('id', 'first_name', 'last_name', 'username', 'email')
            ->orderBy('first_name')
            ->get();

        // Canned Response Templates
        $cannedResponses = CannedResponse::where('is_shared', true)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(fn($c) => [
                'title' => $c->title,
                'message' => $c->content,
                'shortcut_code' => $c->shortcut_code,
            ]);

        return Inertia::render('Admin/Tickets/Show', [
            'ticket' => $ticket,
            'staff' => $staff,
            'cannedResponses' => $cannedResponses,
        ]);
    }

    /**
     * Submit staff response to a support ticket.
     */
    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'message' => ['required', 'string'],
            'status' => ['required', 'in:answered,in_progress,closed,waiting'],
        ]);

        $ticket->replies()->create([
            'user_id' => auth()->id() ?: 1,
            'message' => $validated['message'],
            'is_staff' => true,
        ]);

        $updateData = ['status' => $validated['status']];
        if ($validated['status'] === 'closed') {
            $updateData['closed_at'] = now();
        } else {
            $updateData['closed_at'] = null;
        }

        $ticket->update($updateData);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'ticket_replied',
            'description' => "Replied to ticket #{$ticket->ticket_no} (Status: {$validated['status']}).",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['ticket_no' => $ticket->ticket_no, 'status' => $validated['status']],
        ]);

        return back()->with('success', 'Staff reply posted successfully.');
    }

    /**
     * Update ticket metadata (status, priority, department, assigned staff).
     */
    public function update(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'status' => ['nullable', 'in:open,in_progress,waiting,answered,closed'],
            'priority' => ['nullable', 'in:low,medium,high,critical'],
            'department' => ['nullable', 'in:technical,billing,sales,abuse'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        if (isset($validated['status']) && $validated['status'] === 'closed') {
            $validated['closed_at'] = now();
        } elseif (isset($validated['status']) && $validated['status'] !== 'closed') {
            $validated['closed_at'] = null;
        }

        $ticket->update(array_filter($validated, fn($v) => !is_null($v)));

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'ticket_updated',
            'description' => "Updated metadata for ticket #{$ticket->ticket_no}.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => $validated,
        ]);

        return back()->with('success', "Ticket #{$ticket->ticket_no} updated.");
    }

    /**
     * Close a support ticket.
     */
    public function close(SupportTicket $ticket): RedirectResponse
    {
        $this->authorize('create', User::class);

        $ticket->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'ticket_closed',
            'description' => "Closed ticket #{$ticket->ticket_no}.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['status' => $ticket->getOriginal('status')],
            'new_values' => ['status' => 'closed'],
        ]);

        return back()->with('success', "Ticket #{$ticket->ticket_no} has been closed.");
    }

    /**
     * Delete a support ticket and its replies.
     */
    public function destroy(SupportTicket $ticket): RedirectResponse
    {
        $this->authorize('create', User::class);

        $num = $ticket->ticket_no;
        $ticket->delete();

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'ticket_deleted',
            'description' => "Deleted ticket #{$num}.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['ticket_no' => $num],
            'new_values' => [],
        ]);

        return redirect()->route('admin.tickets.index')
            ->with('success', "Ticket #{$num} deleted.");
    }
}
