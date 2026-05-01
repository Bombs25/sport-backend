<?php

namespace App\Http\Controllers\Api\V1\Posts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Posts\PostCommentResponseStoreRequest;
use App\Jobs\AddCommentResponse;
use App\Jobs\CommentJobNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Bus;

class PostCommentResponseStoreController extends Controller
{
    public function __invoke(PostCommentResponseStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        Bus::chain([
            new AddCommentResponse(
                (int) $validated['post_id'],
                (int) $validated['comment_id'],
                (int) $request->user()->id,
                (string) $validated['post_type'],
                (string) $validated['response'],
                $validated['responded_to_who'] ?? null,
                (bool) $validated['is_reponse_to_main_comment'],
            ),
            new CommentJobNotification(
                (int) $validated['post_id'],
                (int) $request->user()->id,
                (string) $validated['post_type'],
                (string) $validated['response'],
                true,
            ),
        ])->onConnection('post_notifications')->dispatch();

        return response()->json([
            'message' => __('Réponse au commentaire en cours de traitement.'),
        ], 202);
    }
}
