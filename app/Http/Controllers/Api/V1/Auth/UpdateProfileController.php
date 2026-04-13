<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\UpdateProfileRequest;
use App\Services\Auth\UpdateProfileService;
use App\Services\Register\RegisterUserPayloadBuilder;
use Illuminate\Http\JsonResponse;

/**
 * Ce qu'il fait : `PATCH` authentifié pour modifier le profil utilisateur (partiel) et renvoyer le payload `user`.
 *
 * Pourquoi : exposer une route dédiée d'édition du profil hors parcours d'inscription.
 */
class UpdateProfileController extends Controller
{
    public function __invoke(
        UpdateProfileRequest $request,
        UpdateProfileService $service,
        RegisterUserPayloadBuilder $payloadBuilder,
    ): JsonResponse {
        $user = $request->user();

        $service->update($user->id, $request->validated());

        return response()->json([
            'user' => $payloadBuilder->build($user->fresh()),
        ]);
    }
}
