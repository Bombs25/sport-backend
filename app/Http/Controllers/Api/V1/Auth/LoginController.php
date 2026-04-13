<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Services\Auth\LoginService;
use App\Services\Register\RegisterUserPayloadBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * Ce qu’il fait : `POST` connexion e-mail / mot de passe ; **refuse** si l’e-mail n’est pas vérifié ; renvoie token
 * Sanctum + payload `user` ; message de succès ; option `accept_terms`.
 *
 * Pourquoi : alignement `MustVerifyEmail` côté connexion ; erreur 422 sur identifiants incorrects ; message dédié
 * si mot de passe bon mais e-mail non vérifié.
 */
class LoginController extends Controller
{
    public function __invoke(
        LoginRequest $request,
        LoginService $loginService,
        RegisterUserPayloadBuilder $payloadBuilder,
    ): JsonResponse {
        $user = $loginService->validatePasswordLogin(
            $request->validated('email'),
            $request->validated('password'),
        );

        if ($user === null) {
            throw ValidationException::withMessages([
                'email' => [__('Identifiants incorrects. Vérifiez votre adresse e-mail et votre mot de passe.')],
            ]);
        }

        if (! $user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => [__('Veuillez vérifier votre adresse e-mail avant de vous connecter.')],
            ]);
        }

        $token = $loginService->createSanctumToken($user);

        return response()->json([
            'message' => __('Connexion réussie.'),
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => $payloadBuilder->build($user),
        ]);
    }
}
