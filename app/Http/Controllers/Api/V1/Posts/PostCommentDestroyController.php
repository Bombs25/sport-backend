<?php

namespace App\Http\Controllers\Api\V1\Posts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Posts\PostCommentDestroyRequest;
use App\Jobs\DeleteComment;
use App\Services\Post\CommentService;
use Illuminate\Http\JsonResponse;

class PostCommentDestroyController extends Controller
{
    public function __invoke(PostCommentDestroyRequest $request, CommentService $service): JsonResponse
    {
        $commentId = (int) $request->validated('comment_id');
        $postId = (int) $request->validated('post_id');
        $postType = (string) $request->validated('post_type');
        $actorUserId = (int) $request->user()->id;

        $service->assertCanDeleteComment(
            $commentId,
            $postId,
            $postType,
            $actorUserId,
        );

        DeleteComment::dispatch(
            $commentId,
            $postId,
            $postType,
            $actorUserId,
        )->onQueue('post_notifications');

        return response()->json([
            'message' => __('Suppression du commentaire en cours de traitement.'),
        ], 202);
    }
}
