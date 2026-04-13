<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\EmailVerifyOtpRequest;
use App\Services\Auth\EmailVerificationOtpService;
use App\Services\Register\RegisterUserPayloadBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * Ce qu’il fait : `POST` authentifié ; vérifie le code OTP e-mail et renvoie le payload `user` mis à jour.
 *
 * Pourquoi : flux React Native « Vérifier » après saisie des 6 chiffres ; pas de lien signé dans l’app mobile.
 */
class EmailVerifyOtpController extends Controller
{
    public function __invoke(
        EmailVerifyOtpRequest $request,
        EmailVerificationOtpService $otpService,
        RegisterUserPayloadBuilder $payloadBuilder,
    ): JsonResponse {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'user' => $payloadBuilder->build($user),
            ]);
        }

        if (! $otpService->verify($user, $request->validated('code'))) {
            throw ValidationException::withMessages([
                'code' => ['Le code est incorrect ou a expiré.'],
            ]);
        }

        return response()->json([
            'user' => $payloadBuilder->build($user->fresh()),
        ]);
    }
}
