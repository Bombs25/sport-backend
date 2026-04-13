<?php

namespace App\Http\Controllers\Api\V1\Register;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Register\RegisterCredentialsRequest;
use App\Services\Register\RegisterCredentialsService;
use App\Services\Register\RegisterUserPayloadBuilder;
use Illuminate\Http\JsonResponse;

/**
 * Ce qu’il fait : endpoint HTTP de la 1re étape d’inscription ; valide la requête (ville + GPS + identité), délègue au service,
 * renvoie token Bearer + payload `user` pour la suite du wizard.
 *
 * Pourquoi : contrôleur invocable unique responsabilité ; aucune règle métier ici (Form Request + service),
 * conformément aux guidelines contrôleurs courts.
 */
class RegisterCredentialsController extends Controller
{
    public function __invoke(
        RegisterCredentialsRequest $request,
        RegisterCredentialsService $service,
        RegisterUserPayloadBuilder $payloadBuilder,
    ): JsonResponse {
        $validated = $request->validated();

        $result = $service->register(
            $validated['email'],
            $validated['password'],
            $validated['city'],
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            $validated['given_name'],
            $validated['family_name'],
        );

        return response()->json([
            'message' => __('Compte créé. Vérifiez votre email pour activer le compte.'),
            'token' => $result['token']->plainTextToken,
            'token_type' => 'Bearer',
            'user' => $payloadBuilder->build($result['user']),
        ], 201);
    }
}
