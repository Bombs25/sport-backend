<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\NotificationPreferencesUpdateRequest;
use App\Services\Account\NotificationPreferencesService;
use Illuminate\Http\JsonResponse;

/**
 * Ce qu'il fait : applique un patch partiel sur les préférences de notifications.
 *
 * Pourquoi : toggle sur l'écran Paramètres > Notifications — chaque switch
 * envoie une mutation ciblée (ex. `{ "social": { "mentions": false } }`).
 */
class NotificationPreferencesUpdateController extends Controller
{
    public function __invoke(
        NotificationPreferencesUpdateRequest $request,
        NotificationPreferencesService $service,
    ): JsonResponse {
        $service->update((int) $request->user()->id, $request->validated());

        return response()->json([
            'preferences' => $service->getForUser((int) $request->user()->id),
        ]);
    }
}
