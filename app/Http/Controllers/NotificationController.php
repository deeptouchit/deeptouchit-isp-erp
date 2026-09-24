<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get unread notification list for topbar dropdown (JSON API).
     */
    public function unreadList(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'unread_count' => 0,
                'notifications' => [],
            ]);
        }

        $isReseller = method_exists($user, 'isResellerUser') ? $user->isResellerUser() : false;

        // Fetch unread notifications, or recent ones if no unread
        $unreadCount = $user->unreadNotifications()->count();

        $notifications = $user->notifications()
            ->latest()
            ->take(15)
            ->get()
            ->map(function ($n) use ($isReseller) {
                $data = $n->data ?? [];
                $ticketId = $data['ticket_id'] ?? null;
                
                // Dynamically resolve link based on viewer role
                $link = $data['link'] ?? '#';
                if ($ticketId) {
                    try {
                        $link = $isReseller
                            ? route('reseller.tickets.show', $ticketId)
                            : route('tenant.tickets.show', $ticketId);
                    } catch (\Throwable $e) {
                        $link = $data['link'] ?? '#';
                    }
                }

                return [
                    'id' => $n->id,
                    'type' => $data['type'] ?? 'general',
                    'title' => $data['title'] ?? ($data['subject'] ?? 'Notification'),
                    'message' => $data['message'] ?? '',
                    'priority' => $data['priority'] ?? 'medium',
                    'sender_name' => $data['sender_name'] ?? null,
                    'link' => $link,
                    'ticket_number' => $data['ticket_number'] ?? null,
                    'read' => $n->read_at !== null,
                    'time_ago' => $n->created_at ? $n->created_at->locale('en')->diffForHumans() : '',
                    'created_at' => $n->created_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $user = Auth::user();

        if ($user) {
            $notification = $user->notifications()->where('id', $id)->first();
            if ($notification) {
                $notification->markAsRead();
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            $user->unreadNotifications->markAsRead();
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }
}
