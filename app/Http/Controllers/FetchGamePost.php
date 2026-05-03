<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\V1\Posts\FetchGamePostRequest;
use App\Services\Post\FetchPostService;
use Illuminate\Http\JsonResponse;

class FetchGamePost extends Controller
{
    public function __invoke(FetchGamePostRequest $request, FetchPostService $fetchPostService): JsonResponse
    {
        $userId = (int) $request->user()->id;
        /** @var array<int, int> $viewed décodé depuis la chaîne JSON `viewed_post_ids` (FormRequest). */
        $viewed = $request->validated('viewed_post_ids') ?? [];
        $limit = (int) ($request->validated('limit') ?? 20);

        $items = $fetchPostService->fetchMatchResultFeed($userId, $viewed, $limit);

        return response()->json([
            'data' => $items,
        ]);
    }
}
