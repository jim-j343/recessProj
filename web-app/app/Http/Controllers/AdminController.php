<?php

namespace App\Http\Controllers;

use App\Models\Blacklist;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
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

        $members = User::withCount('posts')->orderBy('username')->paginate(20);

        return view('admin.dashboard', compact('stats', 'members'));
    }

    // List members with real filters and search (requirements 4 & 7)
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

    // Per-group statistics (requirement 7)
    public function analytics()
    {
        $groups = Group::withCount(['topics', 'memberships'])->get();

        return view('admin.analytics', compact('groups'));
    }

    // Manually blacklist a member — records a real blacklist row
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

    // Lift a blacklist early
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
