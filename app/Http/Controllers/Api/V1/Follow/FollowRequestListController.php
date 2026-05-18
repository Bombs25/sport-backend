<?php

namespace App\Http\Controllers\Api\V1\Follow;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Follow\FollowRequestListRequest;
use App\Services\Follow\FollowService;
use App\Support\PublicImageUrl;
use Illuminate\Http\JsonResponse;

class FollowRequestListController extends Controller
{
    public function __invoke(FollowRequestListRequest $request, FollowService $service): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $limit = (int) ($request->validated('limit') ?? 20);
        $page = $service->listPendingIncomingPaginated(
            $userId,
            $limit,
            $request->validated('cursor'),
        );

        return response()->json([
            'data' => $page['items']->map(static function (object $item): array {
                $handle = $item->handle;

                return [
                    'id' => (int) $item->follow_row_id,
                    'user_id' => (int) $item->id,
                    'name' => $item->name,
                    'handle' => $handle !== null && $handle !== '' ? '@'.$handle : null,
                    'display_name' => $item->display_name,
                    'avatar_url' => PublicImageUrl::from($item->avatar_url),
                    'requested_at' => $item->requested_at,
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
