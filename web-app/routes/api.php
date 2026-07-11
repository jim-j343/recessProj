<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ForumController;
use Illuminate\Support\Facades\Route;

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
});
