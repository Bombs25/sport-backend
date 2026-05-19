<?php

namespace App\Http\Controllers\Api\V1\Follow;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\UserPublicProfileRequest;
use App\Services\Follow\FollowService;
use App\Services\Register\RegisterUserPayloadBuilder;
use Illuminate\Http\JsonResponse;

/**
 * Ce qu'il fait : totaux posts / followers / following d'un utilisateur cible (mêmes règles d'accès que le profil public).
 *
 * Pourquoi : complète `GET /auth/follows/counts` (compte connecté uniquement) pour les écrans profil visiteur.
 */
class UserFollowCountsController extends Controller
{
    public function __invoke(
        UserPublicProfileRequest $request,
        RegisterUserPayloadBuilder $payloadBuilder,
        FollowService $followService,
    ): JsonResponse {
        $viewer = $request->user();
        $targetUserId = (int) $request->validated('user');

        $payloadBuilder->assertViewerCanAccessProfile($viewer, $targetUserId);

        $stats = $followService->profileStatsForUser($targetUserId);

        return response()->json([
            'data' => $stats,
        ]);
    }
}
