<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\EmailChangeRequest;
use App\Services\Auth\EmailChangeOtpService;
use Illuminate\Http\JsonResponse;

/**
 * Ce qu'il fait : envoie un OTP au nouvel e-mail demandé par l'utilisateur connecté.
 *
 * Pourquoi : sécuriser le changement d'e-mail via preuve de possession de la nouvelle adresse.
 */
class EmailChangeRequestOtpController extends Controller
{
    public function __invoke(EmailChangeRequest $request, EmailChangeOtpService $service): JsonResponse
    {
        $service->sendForUser($request->user(), $request->validated('email'));

        return response()->json([
            'message' => __('Un code de confirmation a été envoyé à votre nouvelle adresse e-mail.'),
        ]);
    }
}
