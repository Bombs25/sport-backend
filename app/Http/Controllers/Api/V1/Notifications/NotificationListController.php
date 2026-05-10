<?php

namespace App\Http\Controllers\Api\V1\Notifications;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Notifications\NotificationListRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Liste paginée des notifications « database » du compte connecté.
 */
class NotificationListController extends Controller
{
    private const PER_PAGE = 10;

    public function __invoke(NotificationListRequest $request): JsonResponse
    {
        $user = $request->user();
        $page = max(1, (int) ($request->validated('page') ?? 1));

        $paginator = $user->notifications()
            ->latest()
            ->paginate(self::PER_PAGE, ['*'], 'page', $page);

        $data = $paginator->getCollection()->map(static function (DatabaseNotification $n): array {
            return [
                'id' => $n->id,
                'type' => $n->type,
                'data' => $n->data,
                'read_at' => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at?->toIso8601String(),
                'updated_at' => $n->updated_at?->toIso8601String(),
            ];
        })->values()->all();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
