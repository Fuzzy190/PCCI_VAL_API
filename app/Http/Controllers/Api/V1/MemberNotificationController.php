<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DuesNotificationResource;
use App\Models\DuesNotification;
use App\Models\Member;
use Illuminate\Http\Request;

class MemberNotificationController extends Controller
{
    /**
     * Get all notifications for a member
     * GET /api/v1/members/{memberId}/notifications
     */
    public function index($memberId, Request $request)
    {
        $member = Member::findOrFail($memberId);

        // Authorization: User can only view their own member notifications unless they're admin
        if ($request->user()->hasRole(['super_admin', 'admin'])) {
            // Admins can view any member's notifications
        } else {
            // Regular users can only view notifications for members they're associated with
            // This depends on your authorization logic
            $this->authorize('view', $member);
        }

        $notifications = DuesNotification::where('member_id', $memberId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return DuesNotificationResource::collection($notifications);
    }

    /**
     * Get unread notifications for a member
     * GET /api/v1/members/{memberId}/notifications/unread
     */
    public function unread($memberId, Request $request)
    {
        $member = Member::findOrFail($memberId);

        // Authorization check
        if ($request->user()->hasRole(['super_admin', 'admin'])) {
            // Admins can view any member's notifications
        } else {
            $this->authorize('view', $member);
        }

        $notifications = DuesNotification::where('member_id', $memberId)
            ->unread()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return DuesNotificationResource::collection($notifications);
    }

    /**
     * Mark a single notification as read
     * PUT /api/v1/notifications/{notificationId}/mark-as-read
     */
    public function markAsRead($notificationId, Request $request)
    {
        $notification = DuesNotification::findOrFail($notificationId);

        // Authorization: User can mark their own member's notifications
        if ($request->user()->hasRole(['super_admin', 'admin'])) {
            // Admins can mark any notification
        } else {
            // Verify the notification belongs to a member the user is authorized to view
            abort_if(
                $notification->member_id !== $request->user()->member?->id,
                403,
                'Unauthorized'
            );
        }

        $notification->markAsRead();

        return new DuesNotificationResource($notification);
    }

    /**
     * Mark a single notification as unread
     * PUT /api/v1/notifications/{notificationId}/mark-as-unread
     */
    public function markAsUnread($notificationId, Request $request)
    {
        $notification = DuesNotification::findOrFail($notificationId);

        // Authorization check
        if ($request->user()->hasRole(['super_admin', 'admin'])) {
            // Admins can mark any notification
        } else {
            abort_if(
                $notification->member_id !== $request->user()->member?->id,
                403,
                'Unauthorized'
            );
        }

        $notification->markAsUnread();

        return new DuesNotificationResource($notification);
    }

    /**
     * Mark all notifications as read for a member
     * PUT /api/v1/members/{memberId}/notifications/mark-all-read
     */
    public function markAllAsRead($memberId, Request $request)
    {
        $member = Member::findOrFail($memberId);

        // Authorization check
        if ($request->user()->hasRole(['super_admin', 'admin'])) {
            // Admins can mark any member's notifications
        } else {
            abort_if(
                $memberId !== $request->user()->member?->id,
                403,
                'Unauthorized'
            );
        }

        DuesNotification::where('member_id', $memberId)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        $notifications = DuesNotification::where('member_id', $memberId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return DuesNotificationResource::collection($notifications);
    }

    /**
     * Filter notifications by type
     * GET /api/v1/members/{memberId}/notifications/by-type?type=warning
     */
    public function filterByType($memberId, Request $request)
    {
        $member = Member::findOrFail($memberId);

        // Authorization check
        if ($request->user()->hasRole(['super_admin', 'admin'])) {
            // Admins can view any member's notifications
        } else {
            abort_if(
                $memberId !== $request->user()->member?->id,
                403,
                'Unauthorized'
            );
        }

        $request->validate([
            'type' => 'required|string|in:first_warning,second_warning,final_warning,expired,payment_received',
        ]);

        $notifications = DuesNotification::where('member_id', $memberId)
            ->byType($request->type)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return DuesNotificationResource::collection($notifications);
    }

    /**
     * Get notification statistics for a member
     * GET /api/v1/members/{memberId}/notifications/stats
     */
    public function stats($memberId, Request $request)
    {
        $member = Member::findOrFail($memberId);

        // Authorization check
        if ($request->user()->hasRole(['super_admin', 'admin'])) {
            // Admins can view any member's stats
        } else {
            abort_if(
                $memberId !== $request->user()->member?->id,
                403,
                'Unauthorized'
            );
        }

        $total = DuesNotification::where('member_id', $memberId)->count();
        $unread = DuesNotification::where('member_id', $memberId)->unread()->count();
        $byType = DuesNotification::where('member_id', $memberId)
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type');

        return response()->json([
            'total' => $total,
            'unread' => $unread,
            'by_type' => $byType,
        ]);
    }

    /**
     * Delete a notification
     * DELETE /api/v1/notifications/{notificationId}
     */
    public function destroy($notificationId, Request $request)
    {
        $notification = DuesNotification::findOrFail($notificationId);

        // Authorization check
        if ($request->user()->hasRole(['super_admin', 'admin'])) {
            // Admins can delete any notification
        } else {
            abort_if(
                $notification->member_id !== $request->user()->member?->id,
                403,
                'Unauthorized'
            );
        }

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted successfully',
        ]);
    }
}
