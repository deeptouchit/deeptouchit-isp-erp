<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    /**
     * Display support tickets list with metrics, search and filters.
     */
    public function index(Request $request): Response
    {
        $userId = auth()->id();

        // 1. Metric Counts
        $totalCount = SupportTicket::where('user_id', $userId)->count();
        $openCount = SupportTicket::where('user_id', $userId)->whereIn('status', ['open', 'waiting'])->count();
        $inProgressCount = SupportTicket::where('user_id', $userId)->where('status', 'in_progress')->count();
        $answeredCount = SupportTicket::where('user_id', $userId)->where('status', 'answered')->count();
        $closedCount = SupportTicket::where('user_id', $userId)->where('status', 'closed')->count();

        // 2. Query with search and status filter
        $query = SupportTicket::where('user_id', $userId);

        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'open') {
                $query->whereIn('status', ['open', 'waiting']);
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('ticket_no', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%");
            });
        }

        $tickets = $query->latest('updated_at')->paginate(15)->withQueryString();

        return Inertia::render('Client/Tickets/Index', [
            'tickets' => $tickets,
            'stats' => [
                'total_count' => $totalCount,
                'open_count' => $openCount,
                'in_progress_count' => $inProgressCount,
                'answered_count' => $answeredCount,
                'closed_count' => $closedCount,
            ],
            'filters' => [
                'status' => $request->query('status', 'all'),
                'search' => $request->query('search', ''),
            ]
        ]);
    }

    /**
     * Show form to open a new support ticket.
     */
    public function create(): Response
    {
        $userId = auth()->id();
        $subscriptions = Subscription::where('user_id', $userId)
            ->with('plan:id,name')
            ->get(['id', 'domain', 'plan_id', 'status']);

        $departments = [
            ['id' => 'technical', 'name' => 'Technical Support', 'desc' => 'cPanel, MySQL, SSL, PHP errors & server configuration'],
            ['id' => 'billing', 'name' => 'Billing & Invoices', 'desc' => 'Renewals, payments, bKash/Nagad checkout & statements'],
            ['id' => 'domain', 'name' => 'Domain & DNS', 'desc' => 'Nameserver delegation, DNS records & domain transfers'],
            ['id' => 'migration', 'name' => 'Website Migration', 'desc' => 'Free migration assistance from other cPanel/hosting providers'],
            ['id' => 'general', 'name' => 'General Inquiries', 'desc' => 'Pre-sales questions, upgrades & account assistance'],
        ];

        return Inertia::render('Client/Tickets/Create', [
            'departments' => $departments,
            'subscriptions' => $subscriptions,
        ]);
    }

    /**
     * Store newly created support ticket.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'department' => 'required|string|in:technical,billing,domain,migration,general',
            'priority' => 'required|in:low,medium,high,critical',
            'message' => 'required|string|min:10',
        ]);

        $ticket = SupportTicket::create([
            'user_id' => auth()->id(),
            'ticket_no' => 'TKT-' . strtoupper(Str::random(8)),
            'subject' => $validated['subject'],
            'department' => $validated['department'],
            'priority' => $validated['priority'],
            'message' => $validated['message'],
            'status' => 'open',
        ]);

        return redirect()->route('tickets.show', $ticket->id)
            ->with('success', "Support ticket #{$ticket->ticket_no} opened successfully.");
    }

    /**
     * Display ticket conversation thread.
     */
    public function show(SupportTicket $ticket): Response
    {
        if ($ticket->user_id !== auth()->id() && auth()->user()->role !== 'admin') {
            abort(403);
        }

        return Inertia::render('Client/Tickets/Show', [
            'ticket' => $ticket->load(['replies.user', 'user'])
        ]);
    }

    /**
     * Post a reply to the ticket.
     */
    public function reply(Request $request, SupportTicket $ticket)
    {
        if ($ticket->user_id !== auth()->id() && auth()->user()->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'message' => 'required|string|min:2',
        ]);

        $ticket->replies()->create([
            'user_id' => auth()->id(),
            'message' => $validated['message'],
            'is_staff' => auth()->user()->role === 'admin',
        ]);

        // If client replies, set status to 'waiting' (waiting on staff) and touch updated_at
        $newStatus = auth()->user()->role === 'admin' ? 'answered' : 'waiting';
        $ticket->update([
            'status' => $newStatus,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Reply posted successfully.');
    }

    /**
     * Close the support ticket.
     */
    public function close(SupportTicket $ticket)
    {
        if ($ticket->user_id !== auth()->id() && auth()->user()->role !== 'admin') {
            abort(403);
        }

        $ticket->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return redirect()->route('tickets.show', $ticket->id)
            ->with('success', "Ticket #{$ticket->ticket_no} has been closed.");
    }
}

