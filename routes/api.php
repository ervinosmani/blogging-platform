<?php

use App\Http\Controllers\{AuthController, CategoryController, CommentController, PostController, PostLikeController};
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/posts/search', [PostController::class, 'search']);
Route::get('/posts/category/{name}', [PostController::class, 'filterByCategory']);
Route::get('/posts/slug/{slug}', [PostController::class, 'show']);

Route::get('/categories', [CategoryController::class, 'index']);

// Kategoritë e mbrojtura
Route::middleware('auth:sanctum')->post('/categories', [CategoryController::class, 'store']);

// Komentet
Route::get('posts/{postId}/comments', [CommentController::class, 'index']);
Route::middleware('auth:sanctum')->post('posts/{postId}/comments', [CommentController::class, 'store']);
Route::middleware('auth:sanctum')->put('comments/{id}', [CommentController::class, 'update']);
Route::middleware('auth:sanctum')->delete('comments/{id}', [CommentController::class, 'destroy']);
Route::middleware('auth:sanctum')->post('comments/{id}/like', [CommentController::class, 'toggleLike']);

// Auth
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user', [AuthController::class, 'update']);
});

// Postet (me autentikim)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/posts', [PostController::class, 'index']);
    Route::post('/posts', [PostController::class, 'store']);
    Route::get('/posts/{id}', [PostController::class, 'show']);
    Route::put('/posts/{id}', [PostController::class, 'update']);
    Route::delete('/posts/{id}', [PostController::class, 'destroy']);
    Route::post('/posts/{slug}/publish', [PostController::class, 'publishPost']);
    Route::get('/my-posts', [PostController::class, 'myPosts']);
    Route::post('/posts/{id}/like', [PostLikeController::class, 'toggle']);
});
