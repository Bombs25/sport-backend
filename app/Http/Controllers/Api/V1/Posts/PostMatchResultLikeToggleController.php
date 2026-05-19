<?php

namespace App\Http\Controllers\Api\V1\Posts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Posts\PostMatchResultLikeToggleRequest;
use App\Jobs\MatchResultLikeNotificationJob;
use App\Jobs\ToggleMatchResultLike;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Bus;

class PostMatchResultLikeToggleController extends Controller
{
    public function __invoke(PostMatchResultLikeToggleRequest $request): JsonResponse
    {
        $validated = $request->validated();

        Bus::chain([
            new ToggleMatchResultLike(
                (int) $validated['post_id'],
                (int) $request->user()->id,
                (string) $validated['post_type'],
                (string) $validated['action'],
            ),
            new MatchResultLikeNotificationJob(
                (int) $validated['post_id'],
                (int) $request->user()->id,
                (string) $validated['post_type'],
                (string) $validated['action'],
            ),
        ])->onQueue('post_notifications')->dispatch();

        return response()->json([
            'message' => __('Traitement du like/dislike du résultat en cours.'),
        ], 202);
    }
}
