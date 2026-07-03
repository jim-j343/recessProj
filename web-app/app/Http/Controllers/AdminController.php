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
    // 1. Fetch data using the exact variable names expected by your view
    $totalMembers = \App\Models\User::count();
    $activeToday  = \App\Models\User::where('status', 'active')->count();
    $warned       = \App\Models\User::where('status', 'warned_once')->count(); // or 'warned' depending on your seed data
    $blacklisted  = \App\Models\User::where('status', 'blacklisted')->count();

    // Fetch the members collection for the table at the bottom
    $members = \App\Models\User::orderBy('username')->paginate(20);

    // 2. Pass them all using compact()
    return view('admin.dashboard', compact(
        'totalMembers',
        'activeToday',
        'warned',
        'blacklisted',
        'members'
    ));
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
