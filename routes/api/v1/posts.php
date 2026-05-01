<?php

use App\Http\Controllers\Api\V1\Posts\PostCommentDestroyController;
use App\Http\Controllers\Api\V1\Posts\PostCommentResponseStoreController;
use App\Http\Controllers\Api\V1\Posts\PostCommentStoreController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->middleware('auth:sanctum')->group(function (): void {
    Route::post('posts/{post_id}/comments', PostCommentStoreController::class);
    Route::post('posts/{post_id}/comments/{comment_id}/responses', PostCommentResponseStoreController::class);
    Route::delete('posts/{post_id}/comments/{comment_id}', PostCommentDestroyController::class);
});
