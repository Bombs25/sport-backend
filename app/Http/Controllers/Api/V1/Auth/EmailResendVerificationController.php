<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\EmailVerificationOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as IlluminateResponse;

/**
 * Ce qu’il fait : `POST` authentifié ; renvoie un **nouveau code OTP** si l’e-mail n’est pas encore vérifié.
 *
 * Pourquoi : action « Renvoyer le code » sur l’écran mobile ; noop silencieux si déjà vérifié.
 */
class EmailResendVerificationController extends Controller
{
    public function __invoke(Request $request, EmailVerificationOtpService $otpService): JsonResponse|IlluminateResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Votre adresse e-mail est déjà vérifiée.',
            ]);
        }

        $otpService->sendForUser($user);

        return response()->noContent();
    }
}
