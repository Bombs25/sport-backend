<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\BlockedUserListRequest;
use App\Services\Account\UserBlockService;
use Illuminate\Http\JsonResponse;

/**
 * Ce qu'il fait : renvoie la liste paginée (cursor) des comptes bloqués par
 * l'utilisateur connecté.
 *
 * Pourquoi : alimente `BlockedAccountsScreen` côté mobile (FlatList paginée).
 */
class BlockedUserListController extends Controller
{
    public function __invoke(BlockedUserListRequest $request, UserBlockService $service): JsonResponse
    {
        $payload = $service->listPaginated(
            blockerUserId: (int) $request->user()->id,
            limit: (int) ($request->validated('limit') ?? 20),
            cursor: $request->validated('cursor'),
        );

        return response()->json($payload);
    }
}
