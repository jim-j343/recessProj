<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\ParticipationController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/privacy', fn () => view('legal.privacy'))->name('privacy');
Route::get('/support', fn () => view('legal.support'))->name('support');

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

    // system_role is a non-nullable enum limited to student|lecturer|system_admin,
    // so every authenticated user is caught by one of the branches above.
    // This is just a defensive fallback — send them somewhere real rather than
    // an all-mock page.
    return redirect()->route('login');
})->middleware(['auth', 'verified'])->name('dashboard');

// User Profile routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Notification routes
Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
});

// Forum routes
Route::middleware(['auth', 'not.blacklisted'])->group(function () {
    Route::get('/forum', [TopicController::class, 'index'])->name('forum.index');
    Route::get('/topics/create', [TopicController::class, 'create'])->name('topics.create');
    Route::post('/topics', [TopicController::class, 'store'])->name('topics.store');
    Route::get('/topics/{topic}', [TopicController::class, 'show'])->name('topics.show');
    Route::get('/topics/{topic}/export-pdf', [TopicController::class, 'exportPdf'])->name('topics.pdf');
    Route::post('/topics/{topic}/posts', [PostController::class, 'store'])->name('posts.store');
    Route::delete('/topics/{topic}', [TopicController::class, 'destroy'])->name('topics.destroy');
    Route::post('/topics/{topic}/reply', [TopicController::class, 'reply'])->name('topics.reply');

    // ---> ADD THESE TWO LINES HERE <---
    Route::get('/topics/{topic}/edit', [TopicController::class, 'edit'])->name('topics.edit');
    Route::put('/topics/{topic}', [TopicController::class, 'update'])->name('topics.update');

    Route::post('/posts/{post}/solution', [PostController::class, 'markSolution'])->name('posts.solution');
    Route::post('/posts/{post}/flag', [PostController::class, 'flag'])->name('posts.flag');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
    // Edit a reply
    Route::get('/posts/{post}/edit', [PostController::class, 'edit'])
    ->name('posts.edit');
     // Update a reply
    Route::put('/posts/{post}', [PostController::class, 'update'])
    ->name('posts.update');
});

// Quiz routes
Route::middleware(['auth', 'not.blacklisted'])->group(function () {
    Route::get('/quiz/create', [QuizController::class, 'create'])->name('quiz.create');
    Route::post('/quiz/store', [QuizController::class, 'store'])->name('quiz.store');
    Route::get('/quiz/{id}/preview', [QuizController::class, 'preview'])->name('quiz.preview');
    Route::post('/quiz/{id}/publish', [QuizController::class, 'publish'])->name('quiz.publish');
    Route::get('/quiz/{id}', [QuizController::class, 'show'])->name('quiz.show');
    Route::post('/quiz/{id}/submit', [QuizController::class, 'submit'])->name('quiz.submit');
    Route::get('/quiz/{id}/results', [QuizController::class, 'results'])->name('quiz.results');
});

// Participation routes
Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/participation', [StudentController::class, 'progress'])->name('participation.index');
});

Route::middleware(['auth', 'role:lecturer'])->group(function () {
    Route::get('/participation/grade', [ParticipationController::class, 'grade'])->name('participation.grade');
    Route::post('/participation/grade', [ParticipationController::class, 'store'])->name('participation.store');
});

// Group routes — /groups/create must come before /groups/{group}
Route::middleware(['auth', 'not.blacklisted'])->group(function () {
    Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
    Route::get('/groups/create', [GroupController::class, 'create'])->name('groups.create');
    Route::post('/groups', [GroupController::class, 'store'])->name('groups.store');
    Route::get('/groups/{group}', [GroupController::class, 'show'])->name('groups.show');
    Route::get('/groups/{group}/edit', [GroupController::class, 'edit'])->name('groups.edit');
    Route::put('/groups/{group}', [GroupController::class, 'update'])->name('groups.update');
    Route::post('/groups/{group}/join', [GroupController::class, 'join'])->name('groups.join');
    Route::post('/groups/{group}/leave', [GroupController::class, 'leave'])->name('groups.leave');
    Route::post('/groups/{group}/members/{user}/remove', [GroupController::class, 'removeMember'])->name('groups.members.remove');
    Route::post('/groups/{group}/members/add', [GroupController::class, 'addMember'])->name('groups.members.add');
    Route::delete('/groups/{group}', [GroupController::class, 'destroy'])->name('groups.destroy');
});

// ==========================================
// ROLE-BASED DASHBOARD ROUTE GROUPS
// ==========================================

// Student dashboard — logic lives in StudentController
Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/student/dashboard', [StudentController::class, 'dashboard'])
        ->name('student.dashboard');
});

// Lecturer dashboard
Route::middleware(['auth', 'role:lecturer'])->group(function () {
    Route::get('/lecturer/dashboard', function () {
        $quizzes = \App\Models\Quiz::where('lecturer_id', auth()->id())
            ->latest()->get();
        $quizCount  = $quizzes->count();
        $groupCount = \App\Models\GroupMembership::where('user_id', auth()->id())->count();
        $topicCount = \App\Models\Topic::where('creator_id', auth()->id())->count();
        return view('lecturer.dashboard', compact('quizzes', 'quizCount', 'groupCount', 'topicCount'));
    })->name('lecturer.dashboard');
Route::get('/participation/grade-json', function () {
    $controller = new \App\Http\Controllers\ParticipationController();
    return $controller->gradeJson(request());
})->name('participation.grade.json');
});

// Admin routes — all protected by role:system_admin
Route::middleware(['auth', 'role:system_admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/admin/members', [AdminController::class, 'members'])->name('admin.members');
    Route::get('/admin/analytics', [AdminController::class, 'analytics'])->name('admin.analytics');
    Route::post('/admin/blacklist/{user}', [AdminController::class, 'blacklistMember'])->name('admin.blacklist');
    Route::post('/admin/lift-blacklist/{user}', [AdminController::class, 'liftBlacklist'])->name('admin.liftBlacklist');
    Route::get('/admin/removals', [AdminController::class, 'removals'])->name('admin.removals');
    Route::post('/admin/removals/{removal}/review', [AdminController::class, 'markRemovalReviewed'])->name('admin.removals.review');
    Route::get('/admin/reports', [AdminController::class, 'reports'])->name('admin.reports');
    Route::post('/admin/reports/{report}/review', [AdminController::class, 'markReportReviewed'])->name('admin.reports.review');
});

require __DIR__.'/auth.php';

