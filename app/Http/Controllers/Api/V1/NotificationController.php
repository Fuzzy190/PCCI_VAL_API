<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // Fetch notifications for the logged-in user
    public function index(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'unread_count'  => $user->unreadNotifications->count(),
            // Increased to 100 to ensure all 65+ migrated members show up
            'notifications' => $user->notifications()->take(100)->get()
        ]);
    }

    // Mark a specific notification as read
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->find($id);

        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['message' => 'Notification marked as read']);
    }

    // Mark ALL notifications as read
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'All notifications marked as read']);
    }

    // Delete all notifications (optional clear history)
    public function clearAll(Request $request)
    {
        $request->user()->notifications()->delete();

        return response()->json(['message' => 'Notification history cleared']);
    }
}
