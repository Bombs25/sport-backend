<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ResetPasswordWithOtpRequest;
use App\Services\Auth\PasswordResetOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * Ce qu’il fait : `POST` public (`forgot-password/reset` ou alias `forgot-password/update`) ; vérifie le **code OTP**
 * et applique le **nouveau mot de passe** (étape 2).
 *
 * Pourquoi : aligné flux RN (code puis mot de passe + confirmation) ; invalide les sessions Sanctum existantes.
 */
class ResetPasswordWithOtpController extends Controller
{
    public function __invoke(ResetPasswordWithOtpRequest $request, PasswordResetOtpService $service): JsonResponse
    {
        if (! $service->resetPassword(
            $request->validated('email'),
            $request->validated('code'),
            $request->validated('password'),
        )) {
            throw ValidationException::withMessages([
                'code' => ['Le code est incorrect ou a expiré.'],
            ]);
        }

        return response()->json([
            'message' => __('Votre mot de passe a été mis à jour. Vous pouvez vous connecter.'),
        ]);
    }
}
