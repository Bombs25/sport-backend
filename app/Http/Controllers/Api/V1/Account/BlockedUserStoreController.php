<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\BlockedUserStoreRequest;
use App\Services\Account\UserBlockService;
use Illuminate\Http\JsonResponse;

/**
 * Ce qu'il fait : bloque un autre utilisateur pour l'utilisateur connecté.
 *
 * Pourquoi : action depuis le menu ⋮ d'un profil (`ViewerProfileScreen`). En
 * cas de blocage frais, les follows réciproques sont supprimés en transaction
 * (cf. `UserBlockService::block`).
 */
class BlockedUserStoreController extends Controller
{
    public function __invoke(BlockedUserStoreRequest $request, UserBlockService $service): JsonResponse
    {
        $blockerId = (int) $request->user()->id;
        $blockedId = (int) $request->validated('user_id');

        $created = $service->block($blockerId, $blockedId);

        return response()->json([
            'blocked' => true,
            'created' => $created,
        ], $created ? 201 : 200);
    }
}
