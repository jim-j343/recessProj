<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Topic;
use App\Models\Post;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    // Main admin dashboard — high level overview stats
    public function dashboard()
    {
        $stats = [
            'total_users'   => User::count(),
            'active_users'  => User::where('status', 'active')->count(),
            'blacklisted'   => User::where('status', 'blacklisted')->count(),
            'total_topics'  => Topic::count(),
            'total_posts'   => Post::count(),
            'flagged_posts' => Post::where('is_flagged', true)->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }

    // List all members for admin management (requirement 7)
    public function members()
    {
        $members = User::orderBy('username')->paginate(20);

        return view('admin.members', compact('members'));
    }

    // View per-group statistics (requirement 7)
    public function analytics()
    {
        $groups = Group::withCount(['topics', 'memberships'])->get();

        return view('admin.analytics', compact('groups'));
    }

    // Manually blacklist a member
    public function blacklistMember(Request $request, User $user)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string'],
        ]);

        $user->update(['status' => 'blacklisted']);

        // Note: also create a row in the blacklist table here once
        // the Blacklist model exists, recording reason and expiry.

        return back()->with('success', "{$user->username} has been blacklisted.");
    }

    // Manually lift a blacklist
    public function liftBlacklist(User $user)
    {
        if (Auth::id() !== $user->user_id) {
            $user->update(['status' => 'active']);
        }

        return back()->with('success', "{$user->username}'s blacklist has been lifted.");
    }
}
