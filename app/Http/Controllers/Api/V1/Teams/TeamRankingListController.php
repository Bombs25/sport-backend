<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Contracts\Stats\SeasonStrategy;
use App\Contracts\Stats\StatsRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Teams\TeamRankingListRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Ce qu'il fait : renvoie le classement des equipes pour un sport et une annee donnes.
 *
 * Pourquoi : alimenter l'ecran "Classement Equipes" sans dupliquer la logique saisonniere.
 * SeasonStrategy: calcule la fenetre de saison (flexible via le binding du container).
 * StatsRepository: charge le classement avec RANK() en Query Builder (cf. §1.7 schema).
 */
class TeamRankingListController extends Controller
{
    public function __invoke(
        TeamRankingListRequest $request,
        SeasonStrategy $seasonStrategy,
        StatsRepository $statsRepository,
    ): JsonResponse {
        $validated = $request->validated();
        $sportId = (int) $validated['sport_id'];
        $page = max(1, (int) ($validated['page'] ?? 1));
        $perPage = 10;
        $year = isset($validated['year'])
            ? (int) $validated['year']
            : (int) CarbonImmutable::now()->year;

        $referenceDate = CarbonImmutable::create($year, 1, 1, 0, 0, 0);
        $seasonWindow = $seasonStrategy->resolveWindowForDate($referenceDate);

        $rankings = $statsRepository->loadSportRanking($sportId, $seasonWindow, $page, $perPage);
        $hasMoreProbePage = ($page * $perPage) + 1;
        $hasMore = $statsRepository->loadSportRanking($sportId, $seasonWindow, $hasMoreProbePage, 1) !== [];

        $userTeamIds = DB::table('team_members')
            ->where('user_id', (int) $request->user()->id)
            ->where('status', 'active')
            ->pluck('team_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $rankings = array_map(
            static fn (array $row): array => $row + [
                'is_current_user_team' => in_array($row['team_id'], $userTeamIds, true),
            ],
            $rankings,
        );

        return response()->json([
            'data' => [
                'sport_id' => $sportId,
                'year' => $year,
                'season_key' => $seasonWindow->key,
                'rankings' => $rankings,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'has_more' => $hasMore,
                ],
            ],
        ]);
    }
}
