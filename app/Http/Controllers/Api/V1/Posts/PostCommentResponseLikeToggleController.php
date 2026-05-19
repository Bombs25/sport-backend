<?php

namespace App\Http\Controllers\Api\V1\Posts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Posts\PostCommentResponseLikeToggleRequest;
use App\Jobs\ResponseCommentLikeNotificationJob;
use App\Jobs\ToggleResponseCommentLike;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Bus;

class PostCommentResponseLikeToggleController extends Controller
{
    public function __invoke(PostCommentResponseLikeToggleRequest $request): JsonResponse
    {
        $validated = $request->validated();

        Bus::chain([
            new ToggleResponseCommentLike(
                (int) $validated['post_id'],
                (int) $validated['comment_id'],
                (int) $validated['response_id'],
                (int) $request->user()->id,
                (string) $validated['post_type'],
                (string) $validated['action'],
            ),
            new ResponseCommentLikeNotificationJob(
                (int) $validated['post_id'],
                (int) $validated['comment_id'],
                (int) $validated['response_id'],
                (int) $request->user()->id,
                (string) $validated['post_type'],
                (string) $validated['action'],
            ),
        ])->onQueue('post_notifications')->dispatch();

        return response()->json([
            'message' => __('Traitement du like/dislike de la réponse en cours.'),
        ], 202);
    }
}
