<?php

namespace App\Http\Controllers\Api\V1\Follow;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Follow\FollowListRequest;
use App\Http\Support\Follow\FollowListResponseBuilder;
use Illuminate\Http\JsonResponse;

class FollowListController extends Controller
{
    public function __invoke(FollowListRequest $request, FollowListResponseBuilder $responseBuilder): JsonResponse
    {
        $userId = (int) $request->user()->id;

        return $responseBuilder->toResponse(
            listOwnerUserId: $userId,
            viewerId: $userId,
            type: $request->validated('type'),
            limit: (int) ($request->validated('limit') ?? 10),
            cursor: $request->validated('cursor'),
            includeEmail: true,
        );
    }
}
