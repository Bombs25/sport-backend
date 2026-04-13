<?php

namespace App\Http\Controllers\Api\V1\Register;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Register\RegisterProfileRequest;
use App\Services\Register\RegisterProfileService;
use App\Services\Register\RegisterUserPayloadBuilder;
use Illuminate\Http\JsonResponse;

/**
 * Ce qu’il fait : `PATCH` authentifié pour l’étape profil (prénom, nom, pseudo, date de naissance) et renvoie le `user` JSON.
 *
 * Pourquoi : correspond à l’écran « Parlez-nous de vous » ; séparation validation (Form Request) / persistance (service).
 */
class RegisterProfileController extends Controller
{
    public function __invoke(
        RegisterProfileRequest $request,
        RegisterProfileService $service,
        RegisterUserPayloadBuilder $payloadBuilder,
    ): JsonResponse {
        $user = $request->user();

        $service->update(
            $user->id,
            $request->validated('given_name'),
            $request->validated('family_name'),
            $request->validated('handle'),
            $request->validated('birth_date'),
        );

        return response()->json([
            'user' => $payloadBuilder->build($user->fresh()),
        ]);
    }
}
