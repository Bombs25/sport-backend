<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Contracts\Stats\StatsRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Teams\TeamRankingListRequest;
use Illuminate\Http\JsonResponse;

/**
 * Ce qu'il fait : renvoie les annees disponibles pour le dropdown de classement d'un sport.
 *
 * Pourquoi : eviter d'afficher des annees sans donnees dans l'UI classement.
 */
class TeamRankingYearsListController extends Controller
{
    public function __invoke(TeamRankingListRequest $request, StatsRepository $statsRepository): JsonResponse
    {
        $sportId = (int) $request->validated('sport_id');
        $years = $statsRepository->loadAvailableRankingYears($sportId);

        return response()->json([
            'data' => [
                'sport_id' => $sportId,
                'years' => $years,
            ],
        ]);
    }
}
