<?php

namespace App\Http\Controllers\Api\V1\Register;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Register\RegisterLocationRequest;
use App\Services\Register\RegisterLocationService;
use App\Services\Register\RegisterUserPayloadBuilder;
use Illuminate\Http\JsonResponse;

/**
 * Ce qu’il fait : `PATCH` authentifié pour mettre à jour localisation (lat/lng optionnels, ville, adresse) et renvoyer le `user` JSON.
 *
 * Pourquoi : étape maquette après les coordonnées déjà prises à l’inscription ; cast des nombres JSON pour éviter
 * des surprises de type côté MySQL ; réponse homogène via `RegisterUserPayloadBuilder`.
 */
class RegisterLocationController extends Controller
{
    public function __invoke(
        RegisterLocationRequest $request,
        RegisterLocationService $service,
        RegisterUserPayloadBuilder $payloadBuilder,
    ): JsonResponse {
        $user = $request->user();

        $service->update(
            $user->id,
            $this->toFloatOrNull($request->validated('latitude')),
            $this->toFloatOrNull($request->validated('longitude')),
            $request->validated('city'),
            $request->validated('address_line'),
        );

        return response()->json([
            'user' => $payloadBuilder->build($user->fresh()),
        ]);
    }

    private function toFloatOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }
}
