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
    $members = \App\Models\User::withCount('posts')->orderBy('username')->paginate(20);

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
     public function members(Request $request)
    {
        $filter = $request->query('filter', 'all');
        $search = $request->query('search');

        $query = User::withCount('posts')
            ->with([
                'warnings'   => fn ($q) => $q->orderByDesc('issued_at'),
                'blacklists' => fn ($q) => $q->orderByDesc('blacklisted_at'),
            ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($filter === 'blacklisted') {
            $query->where('status', 'blacklisted');
        } elseif ($filter === 'active') {
            $query->where('status', 'active');
        } elseif ($filter === 'warning') {
            $query->whereHas('warnings', fn ($q) => $q->where('is_heeded', false));
        }

        $members = $query->orderBy('username')->paginate(10)->withQueryString();

        return view('admin.members', compact('members', 'filter', 'search'));
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
            'reason' => ['required', 'string', 'max:500'],
            'days'   => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $user->update(['status' => 'blacklisted']);

        // Blacklist rows need a group context — use the member's first group
        $groupId = GroupMembership::where('user_id', $user->user_id)->value('group_id');

        if ($groupId) {
           Blacklist::create([
                'user_id'        => $user->user_id,
                'group_id'       => $groupId,
                'reason'         => $validated['reason'],
                'blacklisted_at' => now(),
                'expires_at'     => now()->addDays($validated['days']),
            ]);
        }

        return back()->with('success', "{$user->username} has been blacklisted for {$validated['days']} days.");
    }

    // Manually lift a blacklist
    public function liftBlacklist(User $user)
    {
        $user->update(['status' => 'active']);

        Blacklist::where('user_id', $user->user_id)
            ->whereNull('lifted_by')
            ->where('expires_at', '>', now())
            ->update(['lifted_by' => Auth::id(), 'expires_at' => now()]);

        return back()->with('success', "{$user->username}'s blacklist has been lifted.");
    }
}
