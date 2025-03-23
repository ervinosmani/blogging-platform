<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::get('posts/search', [PostController::class, 'search']);
Route::get('posts/category/{name}', [PostController::class, 'filterByCategory']);

Route::apiResource('posts', PostController::class);

// Routes shtese per like dhe publikim
Route::post('posts/{slug}/like', [PostController::class, 'likePost']);
Route::post('posts/{slug}/publish', [PostController::class, 'publishPost']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('posts/{postId}/comments', [CommentController::class, 'index']); //Komentet mund te lexohen nga te gjithe
Route::post('posts/{postId}/comments', [CommentController::class, 'store'])->middleware('auth:sanctum'); //Vetem perdoruesit e regjistruar mund te krijojne komente

Route::middleware('auth:sanctum')->group(function () {
    Route::put('comments/{id}', [CommentController::class, 'update']); // Vetem autori mund ta perditesoje
    Route::delete('comments/{id}', [CommentController::class, 'destroy']); // Vetem autori mund ta fshije
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user', [AuthController::class, 'update']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/posts', [PostController::class, 'store']);
    Route::put('/posts/{id}', [PostController::class, 'update']);
    Route::delete('/posts/{id}', [PostController::class, 'destroy']);
});

Route::post('comments/{id}/like', [CommentController::class, 'likeComment']);