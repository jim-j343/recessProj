<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

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
});

// Admin routes
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
});

// Quiz routes
Route::middleware(['auth'])->group(function () {
    Route::get('/quiz/{id}', function ($id) {
        return view('quiz.show');
    })->name('quiz.show');
    Route::get('/quiz/create', function () {
        return view('quiz.create');
    })->name('quiz.create');
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

// Admin routes (additional pages)
Route::middleware(['auth'])->group(function () {
    Route::get('/admin/members', function () {
        return view('admin.members');
    })->name('admin.members');
    Route::get('/admin/analytics', function () {
        return view('admin.analytics');
    })->name('admin.analytics');
});

require __DIR__.'/auth.php';
