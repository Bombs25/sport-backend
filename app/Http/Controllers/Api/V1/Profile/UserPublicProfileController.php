<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\UserPublicProfileRequest;
use App\Services\Register\RegisterUserPayloadBuilder;
use Illuminate\Http\JsonResponse;

/**
 * Ce qu'il fait : renvoie le profil d'un autre utilisateur (followers / following / recherche) pour le client connecté.
 *
 * Pourquoi : complète les listes follow sans exposer l'e-mail aux tiers ; respecte `is_private` + follow accepté.
 */
class UserPublicProfileController extends Controller
{
    public function __invoke(
        UserPublicProfileRequest $request,
        RegisterUserPayloadBuilder $payloadBuilder,
    ): JsonResponse {
        return response()->json([
            'user' => $payloadBuilder->buildForViewer(
                $request->user(),
                (int) $request->validated('user'),
            ),
        ]);
    }
}
