<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::apiResource('posts', PostController::class);

// Routes shtese per like dhe publikim
Route::post('posts/{slug}/like', [PostController::class, 'likePost']);
Route::post('posts/{slug}/publish', [PostController::class, 'publishPost']);

Route::get('posts/{postId}/comments', [CommentController::class, 'index']); //Komentet mund te lexohen nga te gjithe
Route::post('posts/{postId}/comments', [CommentController::class, 'store'])->middleware('auth:sanctum'); //Vetem perdoruesit e regjistruar mund te krijojne komente

Route::middleware('auth:sanctum')->group(function () {
    Route::put('comments/{id}', [CommentController::class, 'update']); // Vetem autori mund ta perditesoje
    Route::delete('comments/{id}', [CommentController::class, 'destroy']); // Vetem autori mund ta fshije
});