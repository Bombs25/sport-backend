<?php

namespace App\Http\Controllers\Api\V1\Register;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Register\RegisterHandleAvailabilityRequest;
use App\Repositories\RegisterUserReader;
use Illuminate\Http\JsonResponse;

/**
 * Ce qu’il fait : `GET` public (Bearer optionnel) qui indique si un `handle` est encore libre dans `user_profiles`.
 *
 * Pourquoi : feedback UI maquette (« ce pseudo est disponible ») ; exclusion du compte courant si déjà authentifié
 * pour permettre de garder son propre handle lors d’une vérif.
 */
class RegisterHandleAvailabilityController extends Controller
{
    public function __invoke(
        RegisterHandleAvailabilityRequest $request,
        RegisterUserReader $reader,
    ): JsonResponse {
        $handle = $request->validated('handle');
        $exceptUserId = $request->user()?->id;

        return response()->json([
            'handle' => $handle,
            'available' => $reader->handleIsAvailable($handle, $exceptUserId),
        ]);
    }
}
