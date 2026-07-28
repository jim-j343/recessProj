<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ForumController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\ProfileApiController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GroupApiController;
use App\Http\Controllers\Api\QuizApiController;
use App\Http\Controllers\Api\LecturerApiController;
use App\Http\Controllers\Api\StudentApiController;

/*
|--------------------------------------------------------------------------
| API routes (token auth for the ACES desktop client)
|--------------------------------------------------------------------------
| All routes are prefixed with /api. Public endpoints issue a Sanctum
| personal access token; protected endpoints require
| `Authorization: Bearer <token>`.
*/

// Public — issue a token
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Protected — require a valid token
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user',    [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Profile
    Route::patch('/profile',          [ProfileApiController::class, 'update']);
    Route::patch('/profile/password', [ProfileApiController::class, 'updatePassword']);

    // Notifications
    Route::get('/notifications',                        [NotificationApiController::class, 'index']);
    Route::get('/notifications/all',                    [NotificationApiController::class, 'all']);
    Route::post('/notifications/read-all',              [NotificationApiController::class, 'markAllRead']);
    Route::post('/notifications/{id}/read',             [NotificationApiController::class, 'markRead']);

    // Forum (topics + posts)
    Route::get('/topics',                 [ForumController::class, 'index']);
    Route::post('/topics',                [ForumController::class, 'store']);
    Route::get('/topics/{topic}',         [ForumController::class, 'show']);
    Route::put('/topics/{topic}',         [ForumController::class, 'update']);
    Route::delete('/topics/{topic}',      [ForumController::class, 'destroy']);
    Route::post('/topics/{topic}/posts',  [ForumController::class, 'storePost']);
    Route::get('/topics/{topic}/export-pdf', [\App\Http\Controllers\TopicController::class, 'exportPdf']);
    Route::post('/posts/{post}/flag',     [ForumController::class, 'flagPost']);
    // The desktop client calls /report for the same action — alias it rather
    // than forcing a Java rebuild
    Route::post('/posts/{post}/report',   [ForumController::class, 'flagPost']);
    Route::put('/posts/{post}',           [ForumController::class, 'updatePost']);
    Route::delete('/posts/{post}',        [ForumController::class, 'destroyPost']);
    Route::get('/participation/grade-json', [\App\Http\Controllers\ParticipationController::class, 'gradeJson']);
    Route::post('/participation/grade-json', [\App\Http\Controllers\ParticipationController::class, 'saveGrades']);

        // Groups
    Route::get('/groups', [GroupApiController::class, 'index']);
    Route::post('/groups', [GroupApiController::class, 'store']);
    Route::put('/groups/{group}', [GroupApiController::class, 'update']);
    Route::delete('/groups/{group}', [GroupApiController::class, 'destroy']);
    Route::post('/groups/{group}/join', [GroupApiController::class, 'join']);
    Route::post('/groups/{group}/leave', [GroupApiController::class, 'leave']);
    Route::get('/groups/{group}/members', [GroupApiController::class, 'members']);
    Route::patch('/groups/{group}/members/{userId}/approve', [GroupApiController::class, 'approve']);
    Route::post('/groups/{group}/add-member', [GroupApiController::class, 'addMember']);
    Route::delete('/groups/{group}/members/{userId}', [GroupApiController::class, 'removeMember']);

    // Quizzes
    Route::get('/quizzes', [QuizApiController::class, 'index']);
    Route::get('/quizzes/my', [QuizApiController::class, 'myQuizzes']);
    Route::post('/quizzes', [QuizApiController::class, 'store']);
    Route::post('/quizzes/{id}/publish', [QuizApiController::class, 'publish']);
    Route::get('/quizzes/{id}', [QuizApiController::class, 'show']);
    Route::post('/quizzes/{id}/submit', [QuizApiController::class, 'submit']);
    Route::get('/quizzes/{id}/results', [QuizApiController::class, 'myResult']);
    Route::get('/quizzes/{id}/all-results', [QuizApiController::class, 'allResults']);

    // Student — dashboard/progress extras not covered by /quizzes
    Route::get('/student/dashboard', [StudentApiController::class, 'dashboard']);
    Route::get('/student/progress', [StudentApiController::class, 'progress']);


    // Lecturer
    Route::middleware('role:lecturer')->prefix('lecturer')->group(function () {
        Route::get('/dashboard', [LecturerApiController::class, 'dashboard']);
    });

    Route::middleware('role:system_admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/members', [AdminController::class, 'members']);
        Route::get('/analytics', [AdminController::class, 'analytics']);
        Route::post('/blacklist/{user}', [AdminController::class, 'blacklistMember']);
        Route::post('/lift-blacklist/{user}', [AdminController::class, 'liftBlacklist']);
        Route::get('/removals', [AdminController::class, 'removals']);
        Route::post('/removals/{removal}/review', [AdminController::class, 'markRemovalReviewed']);
        Route::get('/reports', [AdminController::class, 'reports']);
        Route::post('/reports/{report}/review', [AdminController::class, 'markReportReviewed']);
    });

});
