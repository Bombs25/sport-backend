<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Services\Auth\PasswordResetOtpService;
use Illuminate\Http\JsonResponse;

/**
 * Ce qu’il fait : `POST` public ; demande un **code OTP** par e-mail pour réinitialiser le mot de passe (étape 1).
 *
 * Pourquoi : même réponse générique que l’e-mail existe ou non, pour limiter l’énumération de comptes.
 */
class ForgotPasswordController extends Controller
{
    public function __invoke(ForgotPasswordRequest $request, PasswordResetOtpService $service): JsonResponse
    {
        $service->sendResetCode($request->validated('email'));

        return response()->json([
            'message' => __('Si un compte est associé à cette adresse, un code de réinitialisation vient d’y être envoyé.'),
        ]);
    }
}
