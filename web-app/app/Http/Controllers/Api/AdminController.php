<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Blacklist;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Post;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\User;
use App\Models\Warning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard()
    {
        return response()->json([
            'total_members' => User::count(),
            'active_today'  => User::where('status', 'active')->count(),
            'warned'        => User::whereHas('warnings', fn ($q) => $q->where('is_heeded', false))->count(),
            'blacklisted'   => User::where('status', 'blacklisted')->count(),
            'members'       => $this->memberQuery()->orderBy('username')->take(20)->get()->map(fn ($u) => $this->memberPayload($u))->values(),
            'group_settings' => Group::withCount('memberships')
                ->orderBy('name')
                ->get()
                ->map(fn ($g) => [
                    'group_id' => $g->group_id,
                    'name' => $g->name,
                    'course_name' => $g->course_name,
                    'members_count' => $g->memberships_count,
                    'inactivity_warning_days' => $g->inactivity_warning_days,
                    'blacklist_duration_days' => $g->blacklist_duration_days,
                ])
                ->values(),
        ]);
    }

    public function members(Request $request)
    {
        $filter = $request->query('filter', 'all');
        $search = $request->query('search');

        $query = $this->memberQuery();

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

        return response()->json([
            'filter' => $filter,
            'search' => $search,
            'members' => $query->orderBy('username')
                ->paginate(20)
                ->through(fn ($u) => $this->memberPayload($u)),
        ]);
    }

    public function analytics()
    {
        $totalMembers = User::count();
        $activeThisWeek = User::where('last_active_at', '>=', now()->subWeek())->count();
        $warningsThisWeek = Warning::where('issued_at', '>=', now()->subWeek())->count();
        $activeBlacklists = Blacklist::whereNull('lifted_by')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->count();

        $postVolume = collect(range(6, 0))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo);
            return [
                'label' => $date->format('D'),
                'count' => Post::whereDate('created_at', $date->toDateString())->count(),
            ];
        })->values();

        $quizTotalMarks = Question::select('quiz_id', DB::raw('SUM(marks) as total'))
            ->groupBy('quiz_id')
            ->pluck('total', 'quiz_id');

        $groupPerformance = Group::orderBy('name')->get()->map(function ($group) use ($quizTotalMarks) {
            $quizIds = Quiz::where('group_id', $group->group_id)->pluck('quiz_id');

            $percentages = Submission::whereIn('quiz_id', $quizIds)
                ->whereNotNull('submitted_at')
                ->get()
                ->map(function ($submission) use ($quizTotalMarks) {
                    $total = $quizTotalMarks[$submission->quiz_id] ?? 0;
                    return $total > 0 ? ($submission->score / $total) * 100 : null;
                })
                ->filter(fn ($pct) => $pct !== null);

            return [
                'name' => $group->name,
                'avg_pct' => $percentages->count() ? round($percentages->avg(), 1) : null,
                'count' => $percentages->count(),
            ];
        })->filter(fn ($row) => $row['avg_pct'] !== null)->values();

        $groupActivity = Group::orderBy('name')->get()->map(function ($group) {
            $count = Post::whereHas('topic', fn ($q) => $q->where('group_id', $group->group_id))
                ->where('created_at', '>=', now()->subDays(7))
                ->count();

            return ['name' => $group->name, 'count' => $count];
        })->sortByDesc('count')->values();

        return response()->json([
            'total_members' => $totalMembers,
            'active_this_week' => $activeThisWeek,
            'warnings_this_week' => $warningsThisWeek,
            'active_blacklists' => $activeBlacklists,
            'post_volume' => $postVolume,
            'group_performance' => $groupPerformance,
            'group_activity' => $groupActivity,
            'groups' => Group::withCount(['topics', 'memberships'])
                ->orderBy('name')
                ->get()
                ->map(fn ($g) => [
                    'group_id' => $g->group_id,
                    'name' => $g->name,
                    'topics_count' => $g->topics_count,
                    'members_count' => $g->memberships_count,
                ])
                ->values(),
            'recent_activity' => ActivityLog::with(['user', 'group'])
                ->latest('logged_at')
                ->take(5)
                ->get()
                ->map(fn ($a) => [
                    'user' => $a->user?->username ?? 'Unknown',
                    'action' => str_replace('_', ' ', $a->action_type),
                    'group' => $a->group?->name,
                    'logged_at' => optional($a->logged_at)->toISOString(),
                    'logged_at_human' => optional($a->logged_at)->diffForHumans(),
                ])
                ->values(),
        ]);
    }

    public function blacklistMember(Request $request, User $user)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
            'days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $user->update(['status' => 'blacklisted']);
        $groupId = GroupMembership::where('user_id', $user->user_id)->value('group_id');

        if ($groupId) {
            Blacklist::create([
                'user_id' => $user->user_id,
                'group_id' => $groupId,
                'reason' => $validated['reason'],
                'blacklisted_at' => now(),
                'expires_at' => now()->addDays($validated['days']),
            ]);
        }

        return response()->json([
            'message' => "{$user->username} has been blacklisted for {$validated['days']} days.",
            'member' => $this->memberPayload($this->memberQuery()->findOrFail($user->user_id)),
        ]);
    }

    public function liftBlacklist(User $user)
    {
        $user->update(['status' => 'active']);

        Blacklist::where('user_id', $user->user_id)
            ->whereNull('lifted_by')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->update(['lifted_by' => Auth::id(), 'expires_at' => now()]);

        return response()->json([
            'message' => "{$user->username}'s blacklist has been lifted.",
            'member' => $this->memberPayload($this->memberQuery()->findOrFail($user->user_id)),
        ]);
    }

    private function memberQuery()
    {
        return User::withCount('posts')
            ->with([
                'warnings' => fn ($q) => $q->orderByDesc('issued_at'),
                'blacklists' => fn ($q) => $q->orderByDesc('blacklisted_at'),
            ]);
    }

    private function memberPayload(User $member): array
    {
        $activeBlacklist = $member->blacklists
            ->first(fn ($b) => ! $b->lifted_by && ($b->expires_at === null || $b->expires_at->isFuture()));
        $unheededWarnings = $member->warnings->where('is_heeded', false);

        return [
            'user_id' => $member->user_id,
            'username' => $member->username,
            'email' => $member->email,
            'system_role' => $member->system_role,
            'status' => $member->status,
            'last_active_at' => optional($member->last_active_at)->toISOString(),
            'last_active_human' => optional($member->last_active_at)->diffForHumans() ?? 'Never',
            'posts_count' => $member->posts_count ?? 0,
            'unheeded_warning_count' => $unheededWarnings->count(),
            'latest_warning_number' => $unheededWarnings->max('warning_number'),
            'active_blacklist' => $activeBlacklist ? [
                'reason' => $activeBlacklist->reason,
                'expires_at' => optional($activeBlacklist->expires_at)->toISOString(),
                'days_remaining' => $activeBlacklist->expires_at
                    ? max(0, (int) now()->diffInDays($activeBlacklist->expires_at, false))
                    : null,
            ] : null,
        ];
    }
}
