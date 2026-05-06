<?php

namespace App\Http\Controllers\Api\V1\Register;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Register\RegisterSportsRequest;
use App\Services\Register\RegisterSportsService;
use App\Services\Register\RegisterUserPayloadBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Ce qu’il fait : `POST` authentifié pour enregistrer les sports choisis (`sport_ids`, multi-sélection type grille RN),
 * renvoie le `user` JSON + message de succès.
 *
 * Pourquoi : étape « Quels sports pratiquez-vous ? » / Continuer ; délégation au service de sync pour garder le contrôleur minimal.
 */
class RegisterSportsController extends Controller
{
    public function __invoke(
        RegisterSportsRequest $request,
        RegisterSportsService $service,
        RegisterUserPayloadBuilder $payloadBuilder,
    ): JsonResponse {
        $user = $request->user();
        $sportIds = $request->validated('sport_ids');

        $service->sync($user->id, $sportIds);
        Cache::store('app_main_cache')->forever('register:user:sports:'.$user->id, $sportIds);

        return response()->json([
            'message' => __('Vos sports ont été enregistrés.'),
            'user' => $payloadBuilder->build($user->fresh()),
        ]);
    }
}
