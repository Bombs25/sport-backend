<?php

namespace App\Http\Controllers\Api\V1\Posts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Posts\FetchRegularPostFeedRequest;
use App\Services\Post\FetchPostService;
use Illuminate\Http\JsonResponse;

class RegularPostFeedController extends Controller
{
    public function __invoke(FetchRegularPostFeedRequest $request, FetchPostService $fetchPostService): JsonResponse
    {
        $userId = (int) $request->user()->id;
        /** @var array<int, int> $viewed décodé depuis la chaîne JSON `viewed_post_ids` (FormRequest). */
        $viewed = $request->validated('viewed_post_ids') ?? [];
        $feed = $fetchPostService->fetchRegularPostFeedOrdered($userId, $viewed);

        return response()->json([
            'data' => $feed['items'],
            'count' => $feed['count'],
        ]);
    }
}
