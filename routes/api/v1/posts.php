<?php

/*
| Fil « matchs » : résultats (`match_results` avec `status = validated`) publiés par les utilisateurs suivis, hors posts déjà vus.
| GET `/api/v1/auth/posts/feed` — query optionnelle :
| - `viewed_post_ids` : chaîne = `encodeURIComponent(JSON.stringify([12, 34]))` (obligatoirement un tableau stringifié côté frontend).
| - `limit` (défaut 20, max 50).
*/

use App\Http\Controllers\Api\V1\Posts\PostCommentDestroyController;
use App\Http\Controllers\Api\V1\Posts\PostCommentLikeToggleController;
use App\Http\Controllers\Api\V1\Posts\PostCommentResponseDestroyController;
use App\Http\Controllers\Api\V1\Posts\PostCommentResponseLikeToggleController;
use App\Http\Controllers\Api\V1\Posts\PostCommentResponseStoreController;
use App\Http\Controllers\Api\V1\Posts\PostCommentStoreController;
use App\Http\Controllers\Api\V1\Posts\PostMatchResultLikeToggleController;
use App\Http\Controllers\FetchGamePost;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->middleware('auth:sanctum')->group(function (): void {
    Route::get('posts/feed', FetchGamePost::class)
        ->middleware('throttle:60,1')
        ->name('api.v1.auth.posts.feed');
    Route::post('posts/{post_id}/likes', PostMatchResultLikeToggleController::class);
    Route::post('posts/{post_id}/comments', PostCommentStoreController::class);
    Route::post('posts/{post_id}/comments/{comment_id}/likes', PostCommentLikeToggleController::class);
    Route::post('posts/{post_id}/comments/{comment_id}/responses', PostCommentResponseStoreController::class);
    Route::post('posts/{post_id}/comments/{comment_id}/responses/{response_id}/likes', PostCommentResponseLikeToggleController::class);
    Route::delete('posts/{post_id}/comments/{comment_id}/responses/{response_id}', PostCommentResponseDestroyController::class);
    Route::delete('posts/{post_id}/comments/{comment_id}', PostCommentDestroyController::class);
});
