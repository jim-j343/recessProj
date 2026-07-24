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
use App\Models\ParticipationScore;
use App\Models\PostReport;
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
<<<<<<< HEAD
            $quizIds = Quiz::where('group_id', $group->group_id)
                ->when($group->course_name, fn ($q) => $q->orWhere('course_name', $group->course_name))
                ->pluck('quiz_id');
=======
            $quizIds = Quiz::where('group_id', $group->group_id)->pluck('quiz_id');
>>>>>>> c0a0fe073da5b40940d7bd0bb2ce0c10d655d5ed

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

        $lecturerPerformance = User::where('system_role', 'lecturer')->get()->map(function ($lecturer) use ($quizTotalMarks) {
            $quizzes = Quiz::where('lecturer_id', $lecturer->user_id)->get();
            $quizIds = $quizzes->pluck('quiz_id');

<<<<<<< HEAD
            $courses = $quizzes->pluck('course_name')->filter()->unique();

            // Legacy quizzes created before course_name existed only have
            // a group_id — fall back to deriving the course from that
            if ($courses->isEmpty()) {
                $courses = Group::whereIn('group_id', $quizzes->pluck('group_id')->filter()->unique())
                    ->pluck('course_name')
                    ->filter()
                    ->unique()
                    ->values();
            }
=======
            $courses = Group::whereIn('group_id', $quizzes->pluck('group_id')->unique())
                ->pluck('course_name')
                ->filter()
                ->unique()
                ->values();
>>>>>>> c0a0fe073da5b40940d7bd0bb2ce0c10d655d5ed

            $percentages = Submission::whereIn('quiz_id', $quizIds)
                ->whereNotNull('submitted_at')
                ->get()
                ->map(function ($submission) use ($quizTotalMarks) {
                    $total = $quizTotalMarks[$submission->quiz_id] ?? 0;
                    return $total > 0 ? ($submission->score / $total) * 100 : null;
                })
                ->filter(fn ($pct) => $pct !== null);

            $studentsGraded = ParticipationScore::where('awarded_by', $lecturer->user_id)
                ->pluck('user_id')
                ->unique()
                ->count();

            return [
                'name' => $lecturer->username,
                'courses' => $courses,
                'quizCount' => $quizzes->count(),
                'avgPct' => $percentages->count() ? round($percentages->avg(), 1) : null,
                'submissionCount' => $percentages->count(),
                'studentsGraded' => $studentsGraded,
            ];
        })->values();

        return response()->json([
            'total_members' => $totalMembers,
            'active_this_week' => $activeThisWeek,
            'warnings_this_week' => $warningsThisWeek,
            'active_blacklists' => $activeBlacklists,
            'post_volume' => $postVolume,
            'group_performance' => $groupPerformance,
            'group_activity' => $groupActivity,
            'lecturer_performance' => $lecturerPerformance,
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

    public function removals(Request $request)
    {
        $filter = $request->query('filter', 'unreviewed');
        $query = \App\Models\GroupRemoval::with(['group', 'removedUser', 'removedBy', 'reviewedBy']);

        if ($filter === 'unreviewed') {
            $query->where('reviewed', false);
        } elseif ($filter === 'reviewed') {
            $query->where('reviewed', true);
        }

        return response()->json([
            'filter' => $filter,
            'removals' => $query->latest('created_at')->get()->map(function ($r) {
                return [
                    'id' => $r->id,
                    'group_name' => $r->group ? $r->group->name : 'Unknown',
                    'removed_user' => $r->removedUser ? $r->removedUser->username : 'Unknown',
                    'removed_by' => $r->removedBy ? $r->removedBy->username : 'Unknown',
                    'reason' => $r->reason,
                    'reviewed' => (bool) $r->reviewed,
                    'reviewed_by' => $r->reviewedBy ? $r->reviewedBy->username : null,
                    'created_at' => $r->created_at ? $r->created_at->toISOString() : null,
                    'created_at_human' => $r->created_at ? $r->created_at->diffForHumans() : null,
                ];
            })
        ]);
    }

    public function markRemovalReviewed(\App\Models\GroupRemoval $removal)
    {
        $removal->update([
            'reviewed'    => true,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Removal marked as reviewed.',
            'removal' => [
                'id' => $removal->id,
                'reviewed' => true,
                'reviewed_by' => Auth::user()->username,
            ]
        ]);
    }

    public function reports(Request $request)
    {
        $filter = $request->query('filter', 'unreviewed');
        $query = PostReport::with(['post.author', 'post.topic', 'reportedBy', 'reviewedBy']);

        if ($filter === 'unreviewed') {
            $query->where('reviewed', false);
        } elseif ($filter === 'reviewed') {
            $query->where('reviewed', true);
        }

        return response()->json([
            'filter' => $filter,
            'reports' => $query->latest('created_at')->get()->map(function ($r) {
                return [
                    'id' => $r->report_id,
                    'post_content' => $r->post ? $r->post->content : 'Unknown',
                    'topic_title' => $r->post && $r->post->topic ? $r->post->topic->title : 'Unknown',
<<<<<<< HEAD
                    'topic_id' => $r->post && $r->post->topic ? $r->post->topic->topic_id : null,
=======
>>>>>>> c0a0fe073da5b40940d7bd0bb2ce0c10d655d5ed
                    'author' => $r->post && $r->post->author ? $r->post->author->username : 'Unknown',
                    'reported_by' => $r->reportedBy ? $r->reportedBy->username : 'Unknown',
                    'reason' => $r->reason,
                    'reviewed' => (bool) $r->reviewed,
                    'reviewed_by' => $r->reviewedBy ? $r->reviewedBy->username : null,
                    'created_at' => $r->created_at ? $r->created_at->toISOString() : null,
                    'created_at_human' => $r->created_at ? $r->created_at->diffForHumans() : null,
                ];
            })
        ]);
    }

    public function markReportReviewed(PostReport $report)
    {
        $report->update([
            'reviewed'    => true,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Report marked as reviewed.',
            'report' => [
                'id' => $report->report_id,
                'reviewed' => true,
                'reviewed_by' => Auth::user()->username,
            ]
        ]);
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> c0a0fe073da5b40940d7bd0bb2ce0c10d655d5ed
