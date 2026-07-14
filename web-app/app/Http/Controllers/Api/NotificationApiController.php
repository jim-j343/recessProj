<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
    /** GET /api/notifications — unread notifications + unread count */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->user_id;

        $unread = Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->with(['topic'])
            ->latest()
            ->take(10)
            ->get()
            ->map(fn($n) => [
                'notification_id' => $n->notification_id,
                'type'            => $n->type,
                'message'         => $n->message(),
                'is_read'         => $n->is_read,
                'created_at'      => $n->created_at?->toISOString(),
            ]);

        $unreadCount = Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'unread_count'   => $unreadCount,
            'notifications'  => $unread,
        ]);
    }

    /** GET /api/notifications/all — all notifications (read and unread) */
    public function all(Request $request)
    {
        $userId = $request->user()->user_id;

        $allNotifications = Notification::where('user_id', $userId)
            ->latest()
            ->get()
            ->map(function ($n) {
                return [
                    'notification_id' => $n->notification_id,
                    'type'            => $n->type,
                    'message'         => $n->message(),
                    'is_read'         => $n->is_read,
                    'created_at'      => $n->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'unread_count'  => Notification::where('user_id', $userId)->where('is_read', false)->count(),
            'notifications'  => $allNotifications,
        ]);
    }

    /** POST /api/notifications/read-all — mark all as read */
    public function markAllRead(Request $request): JsonResponse
    {
        Notification::where('user_id', $request->user()->user_id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    /** POST /api/notifications/{id}/read — mark one as read */
    public function markRead(Request $request, int $id): JsonResponse
    {
        $notification = Notification::where('notification_id', $id)
            ->where('user_id', $request->user()->user_id)
            ->firstOrFail();

        $notification->update(['is_read' => true]);

        return response()->json(['message' => 'Notification marked as read.']);
    }
}
