<?php

namespace App\Http\Controllers\Api\V1\Posts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Posts\PostCommentResponseDestroyRequest;
use App\Services\Post\CommentResponseService;
use Illuminate\Http\JsonResponse;

class PostCommentResponseDestroyController extends Controller
{
    public function __invoke(PostCommentResponseDestroyRequest $request, CommentResponseService $service): JsonResponse
    {
        $responseId = (int) $request->validated('response_id');
        $commentId = (int) $request->validated('comment_id');
        $postId = (int) $request->validated('post_id');
        $postType = (string) $request->validated('post_type');
        $actorUserId = (int) $request->user()->id;

        $service->deleteCommentResponse(
            $responseId,
            $commentId,
            $postId,
            $postType,
            $actorUserId,
        );

        return response()->json([
            'message' => __('Réponse supprimée.'),
        ]);
    }
}
