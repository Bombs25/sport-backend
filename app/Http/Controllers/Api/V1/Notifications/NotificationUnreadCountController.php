<?php

namespace App\Http\Controllers\Api\V1\Notifications;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Nombre de notifications « database » non lues du compte connecté.
 *
 * Alimente le badge de l'onglet Notifications côté app : comptage exact en
 * base, indépendant de la pagination de la liste.
 */
class NotificationUnreadCountController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $count = $request->user()
            ->unreadNotifications()
            ->count();

        return response()->json([
            'unread_count' => $count,
        ]);
    }
}
