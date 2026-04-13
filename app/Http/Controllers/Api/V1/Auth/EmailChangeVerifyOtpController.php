<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\EmailChangeVerifyRequest;
use App\Services\Auth\EmailChangeOtpService;
use App\Services\Register\RegisterUserPayloadBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * Ce qu'il fait : confirme le code OTP puis applique le nouvel e-mail au compte connecté.
 *
 * Pourquoi : finaliser le changement d'e-mail sans lien magique, adapté au flux mobile OTP.
 */
class EmailChangeVerifyOtpController extends Controller
{
    public function __invoke(
        EmailChangeVerifyRequest $request,
        EmailChangeOtpService $service,
        RegisterUserPayloadBuilder $payloadBuilder,
    ): JsonResponse {
        $user = $request->user();

        if (! $service->verifyAndApply(
            $user,
            $request->validated('email'),
            $request->validated('code'),
        )) {
            throw ValidationException::withMessages([
                'code' => [__('Le code est incorrect, expiré, ou l’e-mail est déjà utilisé.')],
            ]);
        }

        return response()->json([
            'message' => __('Votre adresse e-mail a été mise à jour.'),
            'user' => $payloadBuilder->build($user->fresh()),
        ]);
    }
}
