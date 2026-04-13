<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Ce qu’il fait : `POST` authentifié Bearer ; **révoque le token Sanctum courant** (déconnexion sur cet appareil).
 *
 * Pourquoi : le client RN supprime le token local après succès ; les autres appareils gardent leurs tokens.
 */
class LogoutController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json([
            'message' => __('Déconnexion réussie.'),
        ]);
    }
}
