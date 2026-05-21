<?php

namespace App\Http\Controllers\Api\V1\Posts;

use App\Enums\ImageVariantLongEdge;
use App\Events\ImageProcessingEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Posts\PostStoreRequest;
use App\Jobs\RegularPostPublishedNotificationJob;
use App\Services\Post\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

class PostStoreController extends Controller
{
    public function __invoke(PostStoreRequest $request, PostService $service): JsonResponse
    {
        $validated = $request->validated();
        $media = $request->file('media', []);

        if ($media instanceof UploadedFile) {
            $media = [$media];
        }
        $media = array_values($media);

        $post = $service->createRegularPost(
            (int) $request->user()->id,
            $validated['body'] ?? null,
            (string) ($validated['visibility'] ?? 'public'),
            count($media),
        );

        if ($media !== []) {
            ImageProcessingEvent::dispatch(
                $request->user(),
                $media,
                'post-'.$post['id'],
                contextId: (int) $post['id'],
                variant: ImageVariantLongEdge::Feed,
                type: 'post',
            );
        }

        RegularPostPublishedNotificationJob::dispatch((int) $post['id'], (int) $request->user()->id)
            ->onQueue('post_notifications');

        return response()->json([
            'data' => $post,
            'message' => __('Post publié.'),
        ], 201);
    }
}
