<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Dynamic authenticated dashboard director
Route::get('/dashboard', function (\Illuminate\Http\Request $request) {
    $role = $request->user()->system_role;

    if ($role === 'system_admin') {
        return redirect()->route('admin.dashboard');
    } elseif ($role === 'lecturer') {
        return redirect()->route('lecturer.dashboard');
    } elseif ($role === 'student') {
        return redirect()->route('student.dashboard');
    }

    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// User Profile routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Forum routes
Route::middleware(['auth'])->group(function () {
    Route::get('/forum', [TopicController::class, 'index'])->name('forum.index');
    Route::get('/topics/create', [TopicController::class, 'create'])->name('topics.create');
    Route::post('/topics', [TopicController::class, 'store'])->name('topics.store');
    Route::get('/topics/{topic}', [TopicController::class, 'show'])->name('topics.show');
    Route::get('/topics/{topic}/export-pdf', [TopicController::class, 'exportPdf'])->name('topics.pdf');
    Route::post('/topics/{topic}/posts', [PostController::class, 'store'])->name('posts.store');
    Route::delete('/topics/{topic}', [TopicController::class, 'destroy'])->name('topics.destroy');
    Route::post('/topics/{topic}/reply', [TopicController::class, 'reply'])->name('topics.reply');
    Route::post('/posts/{post}/solution', [PostController::class, 'markSolution'])->name('posts.solution');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
});

// Quiz routes
Route::middleware(['auth'])->group(function () {
    Route::get('/quiz/create', [QuizController::class, 'create'])->name('quiz.create');
    Route::post('/quiz/store', [QuizController::class, 'store'])->name('quiz.store');
    Route::get('/quiz/{id}', [QuizController::class, 'show'])->name('quiz.show');
    Route::post('/quiz/{id}/submit', [QuizController::class, 'submit'])->name('quiz.submit');
    Route::get('/quiz/{id}/results', [QuizController::class, 'results'])->name('quiz.results');
});

// Participation routes
Route::middleware(['auth'])->group(function () {
    Route::get('/participation', function () {
        return view('participation.index');
    })->name('participation.index');
    Route::get('/participation/grade', function () {
        return view('participation.grade');
    })->name('participation.grade');
});

// Group routes — IMPORTANT: /groups/create must come before /groups/{group}
Route::middleware(['auth'])->group(function () {
    Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
    Route::get('/groups/create', [GroupController::class, 'create'])->name('groups.create');
    Route::post('/groups', [GroupController::class, 'store'])->name('groups.store');
    Route::get('/groups/{group}', [GroupController::class, 'show'])->name('groups.show');
    Route::post('/groups/{group}/join', [GroupController::class, 'join'])->name('groups.join');
    Route::post('/groups/{group}/leave', [GroupController::class, 'leave'])->name('groups.leave');
});

// ==========================================
// ROLE-BASED DASHBOARD ROUTE GROUPS
// ==========================================

// Student dashboard
Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/student/dashboard', function () {
        $userId = auth()->id();

        // Groups
        $userGroupIds = \App\Models\GroupMembership::where('user_id', $userId)
            ->where('status', 'active')->pluck('group_id');
        $groupCount = $userGroupIds->count();
        $topicCount = \App\Models\Topic::whereIn('group_id', $userGroupIds)->count();
        $postCount  = \App\Models\Post::where('author_id', $userId)->count();

        // Quizzes — find active and upcoming
        $now = now();
        $allQuizzes = \App\Models\Quiz::whereIn('group_id', $userGroupIds)
            ->where('is_published', true)->get();

        $activeQuiz = $allQuizzes->first(function ($quiz) use ($now) {
            $end = $quiz->start_time->addMinutes($quiz->duration_minutes);
            return $now->between($quiz->start_time, $end);
        });

        $upcomingQuiz = $activeQuiz ? null : $allQuizzes
            ->filter(fn($q) => $q->start_time->isFuture())
            ->sortBy('start_time')
            ->first();

        // Quiz progress
        $publishedCount   = $allQuizzes->count();
        $gradedSubmissions = \App\Models\Submission::with('quiz')
            ->where('user_id', $userId)
            ->whereNotNull('submitted_at')
            ->latest('submitted_at')
            ->get();
        $quizzesCompleted = $gradedSubmissions->count();
        $quizProgress     = $publishedCount > 0
            ? round(($quizzesCompleted / $publishedCount) * 100)
            : 0;
        $averageGrade = $gradedSubmissions->count() > 0
            ? round($gradedSubmissions->avg('score'))
            : null;

        // Participation
        $participationScores = \App\Models\ParticipationScore::where('user_id', $userId)->get();
        $participationTotal  = $participationScores->sum('score');
        $participationAvg    = $participationScores->count() > 0
            ? round($participationScores->avg('score'), 1)
            : null;

        // Overall score blend
        $overallScore = null;
        if ($averageGrade !== null || $participationAvg !== null) {
            $parts = array_filter([$averageGrade, $participationAvg !== null ? $participationAvg * 10 : null]);
            $overallScore = count($parts) > 0 ? round(array_sum($parts) / count($parts)) : null;
        }

        // Warnings
        $latestWarning = \App\Models\Warning::where('user_id', $userId)
            ->where('is_heeded', false)
            ->latest('issued_at')
            ->first();

        // Recent activity
        $recentActivity = \App\Models\ActivityLog::where('user_id', $userId)
            ->latest('logged_at')
            ->take(5)
            ->get();

        $quizzesTotal = $publishedCount;

        return view('student.dashboard', compact(
            'groupCount', 'topicCount', 'postCount',
            'activeQuiz', 'upcomingQuiz',
            'quizzesCompleted', 'quizzesTotal', 'quizProgress',
            'gradedSubmissions', 'averageGrade',
            'participationTotal', 'participationAvg',
            'overallScore', 'latestWarning', 'recentActivity'
        ));
    })->name('student.dashboard');
});

// Lecturer dashboard
Route::middleware(['auth', 'role:lecturer'])->group(function () {
    Route::get('/lecturer/dashboard', function () {
        $quizzes = \App\Models\Quiz::where('lecturer_id', auth()->id())
            ->latest()->get();
        $quizCount = $quizzes->count();
        $groupCount = \App\Models\GroupMembership::where('user_id', auth()->id())->count();
        $topicCount = \App\Models\Topic::where('creator_id', auth()->id())->count();
        return view('lecturer.dashboard', compact('quizzes', 'quizCount', 'groupCount', 'topicCount'));
    })->name('lecturer.dashboard');
});

// Admin routes — all protected by role:system_admin
Route::middleware(['auth', 'role:system_admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/admin/members', [AdminController::class, 'members'])->name('admin.members');
    Route::get('/admin/analytics', [AdminController::class, 'analytics'])->name('admin.analytics');
    Route::post('/admin/blacklist/{user}', [AdminController::class, 'blacklistMember'])->name('admin.blacklist');
    Route::post('/admin/lift-blacklist/{user}', [AdminController::class, 'liftBlacklist'])->name('admin.liftBlacklist');
});

Route::delete('/groups/{group}', [GroupController::class, 'destroy'])->name('groups.destroy');

require __DIR__.'/auth.php';
