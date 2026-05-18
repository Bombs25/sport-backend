<?php

namespace App\Http\Controllers\Api\V1\Follow;

use App\Http\Controllers\Controller;
use App\Services\Follow\FollowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ce qu’il fait : `GET` authentifié qui renvoie les totaux followers / following du compte connecté (relations acceptées).
 *
 * Pourquoi : le client peut charger les compteurs sans paginer la liste des follows.
 */
class FollowCountsController extends Controller
{
    public function __invoke(Request $request, FollowService $service): JsonResponse
    {
        $counts = $service->countsForUser((int) $request->user()->id);

        return response()->json([
            'data' => [
                'followers_count' => $counts['followers_count'],
                'following_count' => $counts['following_count'],
                'pending_requests_count' => $counts['pending_requests_count'],
            ],
        ]);
    }
}
