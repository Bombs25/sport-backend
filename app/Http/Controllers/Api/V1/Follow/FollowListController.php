<?php

namespace App\Http\Controllers\Api\V1\Follow;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Follow\FollowListRequest;
use App\Services\Follow\FollowService;
use Illuminate\Http\JsonResponse;

class FollowListController extends Controller
{
    public function __invoke(FollowListRequest $request, FollowService $service): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $type = $request->validated('type');
        $limit = (int) ($request->validated('limit') ?? 10);
        $page = $service->listForUserPaginated(
            $userId,
            $type,
            $limit,
            $request->validated('cursor'),
        );

        $items = $page['items'];
        $followingLookup = [];
        if ($type === 'followers') {
            $targetIds = $items->pluck('id')->unique()->map(static fn ($id): int => (int) $id)->values()->all();
            $followingLookup = array_fill_keys(
                $service->acceptedFollowingTargetIdsAmong($userId, $targetIds),
                true,
            );
        }

        return response()->json([
            'data' => $items->map(function (object $item) use ($followingLookup, $type): array {
                $id = (int) $item->id;
                $amIFollowing = $type === 'following'
                    ? true
                    : (bool) ($followingLookup[$id] ?? false);

                return [
                    'id' => $id,
                    'name' => $item->name,
                    'email' => $item->email,
                    'handle' => $item->handle,
                    'display_name' => $item->display_name,
                    'avatar_url' => $item->avatar_url,
                    'followed_at' => $item->followed_at,
                    'am_i_following' => $amIFollowing,
                ];
            })->values()->all(),
            'meta' => [
                'next_cursor' => $page['next_cursor'],
                'has_more' => $page['has_more'],
                'per_page' => $limit,
            ],
        ]);
    }
}
