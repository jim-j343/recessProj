<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Topic;
use App\Models\Post;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Blacklist;
use App\Models\Warning;
use App\Models\ActivityLog;
use App\Models\Notification;
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

        // Warnings live in the 'warnings' table (Warning model), not as a
        // user status — 'status' only allows active/blacklisted/suspended,
        // so 'warned_once' never matches and always returned 0
        $warned = \App\Models\User::whereHas('warnings', fn ($q) => $q->where('is_heeded', false))->count();

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

        $totalMembers = User::count();
        $activeThisWeek = User::where('last_active_at', '>=', now()->subWeek())->count();
        $warningsThisWeek = Warning::where('issued_at', '>=', now()->subWeek())->count();
        $activeBlacklists = Blacklist::whereNull('lifted_by')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->count();

        // Post volume for the last 7 days (Mon..Sun-style rolling window)
        $postVolume = collect(range(6, 0))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo);
            return [
                'label' => $date->format('D'),
                'count' => Post::whereDate('created_at', $date->toDateString())->count(),
            ];
        });

        $recentActivity = ActivityLog::with(['user', 'group'])
            ->latest('logged_at')
            ->take(5)
            ->get();

        return view('admin.analytics', compact(
            'groups',
            'totalMembers',
            'activeThisWeek',
            'warningsThisWeek',
            'activeBlacklists',
            'postVolume',
            'recentActivity'
        ));
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

        Notification::notify($user->user_id, 'blacklisted');

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
