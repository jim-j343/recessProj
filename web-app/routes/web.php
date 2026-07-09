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
    Route::get('/student/dashboard', [StudentController::class, 'dashboard'])->name('student.dashboard');
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
