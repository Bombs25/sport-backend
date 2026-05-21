<?php

namespace App\Http\Controllers\Api\V1\Posts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Posts\PostCommentLikeToggleRequest;
use App\Jobs\CommentLikeNotificationJob;
use App\Services\Post\CommentLikeService;
use Illuminate\Http\JsonResponse;

class PostCommentLikeToggleController extends Controller
{
    public function __invoke(PostCommentLikeToggleRequest $request, CommentLikeService $service): JsonResponse
    {
        $validated = $request->validated();
        $userId = (int) $request->user()->id;

        $result = $service->toggleLike(
            (int) $validated['post_id'],
            (int) $validated['comment_id'],
            $userId,
            (string) $validated['post_type'],
            (string) $validated['action'],
        );

        $notifyOwner = (int) $result['comment_owner_id'] !== $userId;

        if ($result['changed'] && $validated['action'] === 'like' && $notifyOwner) {
            CommentLikeNotificationJob::dispatch(
                (int) $validated['post_id'],
                (int) $validated['comment_id'],
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
