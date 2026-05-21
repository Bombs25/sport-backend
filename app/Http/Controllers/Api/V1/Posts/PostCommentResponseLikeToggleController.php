<?php

namespace App\Http\Controllers\Api\V1\Posts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Posts\PostCommentResponseLikeToggleRequest;
use App\Jobs\ResponseCommentLikeNotificationJob;
use App\Services\Post\ResponseCommentLikeService;
use Illuminate\Http\JsonResponse;

class PostCommentResponseLikeToggleController extends Controller
{
    public function __invoke(
        PostCommentResponseLikeToggleRequest $request,
        ResponseCommentLikeService $service,
    ): JsonResponse {
        $validated = $request->validated();
        $userId = (int) $request->user()->id;

        $result = $service->toggleLike(
            (int) $validated['post_id'],
            (int) $validated['comment_id'],
            (int) $validated['response_id'],
            $userId,
            (string) $validated['post_type'],
            (string) $validated['action'],
        );

        $notifyOwner = (int) $result['response_owner_id'] !== $userId;

        if ($result['changed'] && $validated['action'] === 'like' && $notifyOwner) {
            ResponseCommentLikeNotificationJob::dispatch(
                (int) $validated['post_id'],
                (int) $validated['comment_id'],
                (int) $validated['response_id'],
                $userId,
                (string) $validated['post_type'],
                'like',
            )->onQueue('post_notifications');
        }

        return response()->json([
            'data' => [
                'liked' => $result['liked'],
                'likes_count' => $result['likes_count'],
            ],
            'message' => __('Like mis à jour.'),
        ]);
    }
}
