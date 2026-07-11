<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    // Full notifications page, newest first
    public function index()
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->with(['topic', 'post'])
            ->latest()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    // Mark a single notification as read, then send the user to what it's about
    public function markRead(Notification $notification)
    {
        abort_unless($notification->user_id === Auth::id(), 403);

        if (! $notification->is_read) {
            $notification->update(['is_read' => true]);
        }

        return redirect($notification->link());
    }

    // Mark everything as read (used by the "mark all read" button)
    public function markAllRead()
    {
        Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return back();
    }
}
