<?php

namespace App\Http\Controllers\Api\V1\Users;

use App\Http\Controllers\Controller;
use App\Services\Register\RegisterUserPayloadBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ce qu’il fait : `GET` authentifié qui renvoie l’utilisateur courant au même format JSON que login / register.
 *
 * Pourquoi : le client RN peut rafraîchir l’état session après navigation ; évite d’exposer le modèle Eloquent brut.
 */
class CurrentUserController extends Controller
{
    public function __invoke(Request $request, RegisterUserPayloadBuilder $payloadBuilder): JsonResponse
    {
        return response()->json([
            'user' => $payloadBuilder->build($request->user()),
        ]);
    }
}
