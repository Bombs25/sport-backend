<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Services\Account\NotificationPreferencesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ce qu'il fait : renvoie les préférences de notifications de l'utilisateur
 * connecté (JSON merge avec les défauts si la colonne est NULL).
 *
 * Pourquoi : utilisé par l'écran Paramètres > Notifications pour afficher
 * l'état initial des toggles.
 */
class NotificationPreferencesShowController extends Controller
{
    public function __invoke(Request $request, NotificationPreferencesService $service): JsonResponse
    {
        return response()->json([
            'preferences' => $service->getForUser((int) $request->user()->id),
        ]);
    }
}
