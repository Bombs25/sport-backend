<?php

use App\Http\Controllers\Api\V1\Posts\PostCommentDestroyController;
use App\Http\Controllers\Api\V1\Posts\PostCommentLikeToggleController;
use App\Http\Controllers\Api\V1\Posts\PostCommentResponseDestroyController;
use App\Http\Controllers\Api\V1\Posts\PostCommentResponseLikeToggleController;
use App\Http\Controllers\Api\V1\Posts\PostCommentResponseStoreController;
use App\Http\Controllers\Api\V1\Posts\PostCommentStoreController;
use App\Http\Controllers\Api\V1\Posts\PostMatchResultLikeToggleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->middleware('auth:sanctum')->group(function (): void {
    Route::post('posts/{post_id}/likes', PostMatchResultLikeToggleController::class);
    Route::post('posts/{post_id}/comments', PostCommentStoreController::class);
    Route::post('posts/{post_id}/comments/{comment_id}/likes', PostCommentLikeToggleController::class);
    Route::post('posts/{post_id}/comments/{comment_id}/responses', PostCommentResponseStoreController::class);
    Route::post('posts/{post_id}/comments/{comment_id}/responses/{response_id}/likes', PostCommentResponseLikeToggleController::class);
    Route::delete('posts/{post_id}/comments/{comment_id}/responses/{response_id}', PostCommentResponseDestroyController::class);
    Route::delete('posts/{post_id}/comments/{comment_id}', PostCommentDestroyController::class);
});
