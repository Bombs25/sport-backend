<?php

namespace App\Http\Controllers\Api\V1\Posts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Posts\PostCommentStoreRequest;
use App\Jobs\AddComment;
use App\Jobs\CommentJobNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Bus;

class PostCommentStoreController extends Controller
{
    public function __invoke(
        PostCommentStoreRequest $request,
        int $post_id,
    ): JsonResponse {
        $validated = $request->validated();

        Bus::chain([
            new AddComment(
                $post_id,
                (int) $request->user()->id,
                (string) $validated['post_type'],
                (string) $validated['commentaire'],
            ),
            new CommentJobNotification(
                $post_id,
                (int) $request->user()->id,
                (string) $validated['post_type'],
                (string) $validated['commentaire'],
            ),
        ])->onQueue('post_notifications')->dispatch();

        return response()->json([
            'message' => 'Commentaire en cours de traitement.',
        ], 202);
    }
}
