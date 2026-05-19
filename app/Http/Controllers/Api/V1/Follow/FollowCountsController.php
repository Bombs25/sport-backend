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
        $userId = (int) $request->user()->id;
        $stats = $service->profileStatsForUser($userId);
        $counts = $service->countsForUser($userId);

        return response()->json([
            'data' => [
                'posts_count' => $stats['posts_count'],
                'followers_count' => $stats['followers_count'],
                'following_count' => $stats['following_count'],
                'pending_requests_count' => $counts['pending_requests_count'],
            ],
        ]);
    }
}
