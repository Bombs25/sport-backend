<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\PasswordChangeRequest;
use App\Services\Auth\PasswordChangeService;
use Illuminate\Http\JsonResponse;

/**
 * Ce qu'il fait : met à jour le mot de passe du user connecté après vérification de l'ancien mot de passe.
 *
 * Pourquoi : endpoint de paramètres compte sans OTP pour modification directe.
 */
class PasswordChangeController extends Controller
{
    public function __invoke(PasswordChangeRequest $request, PasswordChangeService $service): JsonResponse
    {
        $service->changePassword(
            $request->user(),
            $request->validated('password'),
        );

        return response()->json([
            'message' => __('Votre mot de passe a été mis à jour. Veuillez vous reconnecter.'),
        ]);
    }
}
