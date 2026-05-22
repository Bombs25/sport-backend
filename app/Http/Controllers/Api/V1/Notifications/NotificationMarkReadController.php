<?php

namespace App\Http\Controllers\Api\V1\Notifications;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Marque une notification « database » du compte connecté comme lue.
 *
 * Le lookup via `notifications()` garantit que l'utilisateur ne peut marquer
 * que ses propres notifications (404 sinon). Idempotent : marquer une
 * notification déjà lue ne renvoie pas d'erreur.
 */
class NotificationMarkReadController extends Controller
{
    public function __invoke(Request $request, string $notification): JsonResponse
    {
        $notif = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->first();

        if ($notif === null) {
            abort(404);
        }

        if ($notif->read_at === null) {
            $notif->markAsRead();
        }

        return response()->json([
            'id' => $notif->id,
            'read_at' => $notif->read_at?->toIso8601String(),
        ]);
    }
}
