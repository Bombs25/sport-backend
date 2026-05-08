<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Contracts\Stats\SeasonStrategy;
use App\Contracts\Stats\StatsRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Teams\TeamSeasonStatsRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Ce qu'il fait : expose les agrégés saison PLAYED/WON/LOST/DRAW (plus points) pour une équipe.
 *
 * Pourquoi : écran dashboard « Season Stats » ; saison alignée avec TeamRankingListController.
 */
class TeamSeasonStatsShowController extends Controller
{
    public function __invoke(
        TeamSeasonStatsRequest $request,
        SeasonStrategy $seasonStrategy,
        StatsRepository $statsRepository,
    ): JsonResponse {
        $validated = $request->validated();
        $teamId = (int) $validated['team_id'];
        $year = isset($validated['year'])
            ? (int) $validated['year']
            : (int) CarbonImmutable::now()->year;

        $referenceDate = CarbonImmutable::create($year, 1, 1, 0, 0, 0);
        $seasonWindow = $seasonStrategy->resolveWindowForDate($referenceDate);

        /** @var int|null $sportId */
        $sportId = DB::table('teams')->where('id', $teamId)->value('sport_id');

        $totals = $statsRepository->loadTeamSeasonStats($teamId, (int) $sportId, $seasonWindow);

        return response()->json([
            'data' => [
                'team_id' => $teamId,
                'sport_id' => (int) $sportId,
                'year' => $year,
                'season_key' => $seasonWindow->key,
                'played' => $totals['played'],
                'won' => $totals['won'],
                'lost' => $totals['lost'],
                'draw' => $totals['draw'],
                'point_count' => $totals['point_count'],
            ],
        ]);
    }
}
