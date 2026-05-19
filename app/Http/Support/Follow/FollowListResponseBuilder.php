<?php

namespace App\Http\Support\Follow;

use App\Services\Follow\FollowService;
use App\Support\PublicImageUrl;
use Illuminate\Http\JsonResponse;

/**
 * Assemble la réponse JSON paginée followers / following (compte connecté ou profil cible).
 */
class FollowListResponseBuilder
{
    public function __construct(
        private FollowService $service,
    ) {}

    public function toResponse(
        int $listOwnerUserId,
        int $viewerId,
        string $type,
        int $limit,
        ?string $cursor,
        bool $includeEmail,
    ): JsonResponse {
        $page = $this->service->listForUserPaginated(
            $listOwnerUserId,
            $type,
            $limit,
            $cursor,
        );

        $items = $page['items'];
        $followingLookup = [];
        if ($type === 'followers') {
            $targetIds = $items->pluck('id')->unique()->map(static fn ($id): int => (int) $id)->values()->all();
            $followingLookup = array_fill_keys(
                $this->service->acceptedFollowingTargetIdsAmong($viewerId, $targetIds),
                true,
            );
        }

        return response()->json([
            'data' => $items->map(function (object $item) use ($followingLookup, $type, $includeEmail): array {
                $id = (int) $item->id;
                $amIFollowing = $type === 'following'
                    ? true
                    : (bool) ($followingLookup[$id] ?? false);

                $row = [
                    'id' => $id,
                    'name' => $item->name,
                    'handle' => $item->handle,
                    'display_name' => $item->display_name,
                    'avatar_url' => PublicImageUrl::from($item->avatar_url),
                    'followed_at' => $item->followed_at,
                    'am_i_following' => $amIFollowing,
                ];

                if ($includeEmail) {
                    $row['email'] = $item->email;
                }

                return $row;
            })->values()->all(),
            'meta' => [
                'next_cursor' => $page['next_cursor'],
                'has_more' => $page['has_more'],
                'per_page' => $limit,
            ],
        ]);
    }
}
