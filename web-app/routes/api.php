<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ForumController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GroupApiController;
use App\Http\Controllers\Api\QuizApiController;

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

    // Forum (topics + posts)
    Route::get('/topics',                 [ForumController::class, 'index']);
    Route::post('/topics',                [ForumController::class, 'store']);
    Route::get('/topics/{topic}',         [ForumController::class, 'show']);
    Route::post('/topics/{topic}/posts',  [ForumController::class, 'storePost']);

        // Groups
    Route::get('/groups', [GroupApiController::class, 'index']);
    Route::post('/groups', [GroupApiController::class, 'store']);
    Route::post('/groups/{group}/join', [GroupApiController::class, 'join']);
    Route::get('/groups/{group}/members', [GroupApiController::class, 'members']);
    Route::patch('/groups/{group}/members/{userId}/approve', [GroupApiController::class, 'approve']);

    // Quizzes
    Route::get('/quizzes', [QuizApiController::class, 'index']);
    Route::get('/quizzes/my', [QuizApiController::class, 'myQuizzes']);
    Route::get('/quizzes/{id}', [QuizApiController::class, 'show']);
    Route::post('/quizzes/{id}/submit', [QuizApiController::class, 'submit']);
    Route::get('/quizzes/{id}/results', [QuizApiController::class, 'myResult']);
    Route::get('/quizzes/{id}/all-results', [QuizApiController::class, 'allResults']);

});
