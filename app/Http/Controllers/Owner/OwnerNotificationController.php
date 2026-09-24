<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OwnerNotificationController extends Controller
{
    /**
     * Get unread notifications for owner header dropdown (JSON API)
     */
    public function unreadList()
    {
        $user = auth()->user();

        // If user is owner or super_admin, fetch notifications for this user or all global owner notifications
        $notifications = $user->unreadNotifications()
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->data['type'] ?? 'general',
                    'title' => $notification->data['subject'] ?? 'Notification',
                    'message' => $notification->data['message'] ?? '',
                    'link' => $notification->data['link'] ?? '#',
                    'priority' => $notification->data['priority'] ?? 'normal',
                    'time_ago' => $notification->created_at->diffForHumans(),
                ];
            });

        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark a single notification as read
     */
    public function markAsRead(?Request $request = null, $id = null)
    {
        $request = $request ?? request();
        $id = $id ?? $request->route('id');
        $notification = auth()->user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(?Request $request = null)
    {
        $request = $request ?? request();
        auth()->user()->unreadNotifications->markAsRead();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }
}
