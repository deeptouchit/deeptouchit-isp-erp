<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Tickets/Index', [
            'tickets' => SupportTicket::with('user')->latest()->paginate(15)
        ]);
    }

    public function show(SupportTicket $ticket): Response
    {
        return Inertia::render('Admin/Tickets/Show', [
            'ticket' => $ticket->load(['user', 'replies.user'])
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        $ticket->replies()->create([
            'user_id' => auth()->id(),
            'message' => $validated['message'],
            'is_staff' => true,
        ]);

        $ticket->update(['status' => 'answered']);

        return back()->with('success', 'Reply submitted successfully.');
    }
}
