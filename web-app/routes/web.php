<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PostController;
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
    Route::delete('/topics/{topic}', [TopicController::class, 'destroy'])->name('topics.destroy');
    Route::post('/topics/{topic}/reply', [TopicController::class, 'reply'])->name('topics.reply');
    Route::post('/posts/{post}/solution', [PostController::class, 'markSolution'])->name('posts.solution');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
    });
// Admin routes
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
});

require __DIR__.'/auth.php';
