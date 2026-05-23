<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Services\Account\UserBlockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ce qu'il fait : débloque l'utilisateur `$blockedUserId` pour l'utilisateur
 * connecté (idempotent — pas d'erreur si pas bloqué).
 *
 * Pourquoi : action depuis l'écran « Comptes bloqués » (Paramètres). Les
 * follows ne sont pas re-créés automatiquement après débloquage (par design,
 * l'utilisateur doit re-suivre s'il le souhaite).
 */
class BlockedUserDestroyController extends Controller
{
    public function __invoke(Request $request, int $blockedUserId, UserBlockService $service): JsonResponse
    {
        $service->unblock((int) $request->user()->id, $blockedUserId);

        return response()->json(['blocked' => false]);
    }
}
