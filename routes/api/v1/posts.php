<?php

/*
| Fil « matchs » : résultats (`match_results` avec `status = validated`) publiés par les utilisateurs suivis, hors posts déjà vus.
| GET `/api/v1/auth/posts/feed` — query optionnelle :
| - `viewed_post_ids` : chaîne = `encodeURIComponent(JSON.stringify([12, 34]))` (obligatoirement un tableau stringifié côté frontend).
|
| Fil « regular » : posts publiés dans `posts`, hors posts déjà vus.
| GET `/api/v1/auth/posts/regular/feed` — même format de query `viewed_post_ids`, mais validé contre `posts.id`.
*/

use App\Http\Controllers\Api\V1\Posts\MatchResultShowController;
use App\Http\Controllers\Api\V1\Posts\PostCommentDestroyController;
use App\Http\Controllers\Api\V1\Posts\PostCommentLikeToggleController;
use App\Http\Controllers\Api\V1\Posts\PostCommentResponseDestroyController;
use App\Http\Controllers\Api\V1\Posts\PostCommentResponseLikeToggleController;
use App\Http\Controllers\Api\V1\Posts\PostCommentResponsesListController;
use App\Http\Controllers\Api\V1\Posts\PostCommentResponseStoreController;
use App\Http\Controllers\Api\V1\Posts\PostCommentsListController;
use App\Http\Controllers\Api\V1\Posts\PostCommentStoreController;
use App\Http\Controllers\Api\V1\Posts\PostDestroyController;
use App\Http\Controllers\Api\V1\Posts\PostMatchResultLikeToggleController;
use App\Http\Controllers\Api\V1\Posts\PostStoreController;
use App\Http\Controllers\Api\V1\Posts\RegularPostFeedController;
use App\Http\Controllers\Api\V1\Posts\RegularPostShowController;
use App\Http\Controllers\FetchGamePost;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->middleware('auth:sanctum')->group(function (): void {
    Route::get('posts/feed', FetchGamePost::class)
        ->middleware('throttle:60,1')
        ->name('api.v1.auth.posts.feed');
    Route::get('posts/feed/{match_result_id}', MatchResultShowController::class)
        ->whereNumber('match_result_id')
        ->middleware('throttle:60,1')
        ->name('api.v1.auth.posts.match-result.show');
    Route::get('posts/regular/feed', RegularPostFeedController::class)
        ->middleware('throttle:60,1')
        ->name('api.v1.auth.posts.regular.feed');
    Route::get('posts/regular/{post_id}', RegularPostShowController::class)
        ->whereNumber('post_id')
        ->middleware('throttle:60,1')
        ->name('api.v1.auth.posts.regular.show');
    Route::post('posts', PostStoreController::class)
        ->middleware('throttle:30,1')
        ->name('api.v1.auth.posts.store');
    // Supprime un post régulier (menu ⋮, auteur uniquement). Soft-delete.
    Route::delete('posts/{post_id}', PostDestroyController::class)
        ->whereNumber('post_id')
        ->middleware('throttle:30,1')
        ->name('api.v1.auth.posts.destroy');
    Route::post('posts/{post_id}/likes', PostMatchResultLikeToggleController::class);
    Route::post('posts/{post_id}/comments', PostCommentStoreController::class);
    Route::get('posts/comments', PostCommentsListController::class);
    Route::post('posts/{post_id}/comments/{comment_id}/likes', PostCommentLikeToggleController::class);
    Route::post('posts/{post_id}/comments/{comment_id}/responses', PostCommentResponseStoreController::class);
    Route::get('posts/{post_id}/comments/{comment_id}/responses', PostCommentResponsesListController::class);
    Route::post('posts/{post_id}/comments/{comment_id}/responses/{response_id}/likes', PostCommentResponseLikeToggleController::class);
    Route::delete('posts/{post_id}/comments/{comment_id}/responses/{response_id}', PostCommentResponseDestroyController::class);
    Route::delete('posts/{post_id}/comments/{comment_id}', PostCommentDestroyController::class);
});
