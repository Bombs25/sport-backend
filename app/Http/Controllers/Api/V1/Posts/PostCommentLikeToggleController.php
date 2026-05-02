<?php

namespace App\Http\Controllers\Api\V1\Posts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Posts\PostCommentLikeToggleRequest;
use App\Jobs\CommentLikeNotificationJob;
use App\Jobs\ToggleCommentLike;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Bus;

class PostCommentLikeToggleController extends Controller
{
    public function __invoke(PostCommentLikeToggleRequest $request): JsonResponse
    {
        $validated = $request->validated();

        Bus::chain([
            new ToggleCommentLike(
                (int) $validated['post_id'],
                (int) $validated['comment_id'],
                (int) $request->user()->id,
                (string) $validated['post_type'],
                (string) $validated['action'],
            ),
            new CommentLikeNotificationJob(
                (int) $validated['post_id'],
                (int) $validated['comment_id'],
                (int) $request->user()->id,
                (string) $validated['post_type'],
                (string) $validated['action'],
            ),
        ])->onConnection('post_notifications')->dispatch();

        return response()->json([
            'message' => __('Traitement du like/dislike en cours.'),
        ], 202);
    }
}
