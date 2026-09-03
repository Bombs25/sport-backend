<?php

namespace App\Http\Controllers\Api\V1\Teams;

use App\Contracts\Stats\SeasonStrategy;
use App\Contracts\Stats\StatsRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Teams\TeamRankingListRequest;
use App\Services\Search\TypesenseTeamService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Typesense\Exceptions\TypesenseClientError;

/**
 * Ce qu'il fait : renvoie le classement des equipes pour un sport et une annee donnes.
 *
 * Pourquoi : alimenter l'ecran "Classement Equipes" sans dupliquer la logique saisonniere.
 * Recherche texte : Typesense (noms) puis stats saison MySQL (rang / V-N-D / PTS).
 */
class TeamRankingListController extends Controller
{
    public function __invoke(
        TeamRankingListRequest $request,
        SeasonStrategy $seasonStrategy,
        StatsRepository $statsRepository,
        TypesenseTeamService $typesenseTeams,
    ): JsonResponse {
        $validated = $request->validated();
        $sportId = (int) $validated['sport_id'];
        $region = (string) $validated['region'];
        $page = max(1, (int) ($validated['page'] ?? 1));
        $perPage = 10;
        $year = isset($validated['year'])
            ? (int) $validated['year']
            : (int) CarbonImmutable::now()->year;
        $q = isset($validated['q']) ? trim((string) $validated['q']) : null;
        if ($q === '') {
            $q = null;
        }

        $referenceDate = CarbonImmutable::create($year, 1, 1, 0, 0, 0);
        $seasonWindow = $seasonStrategy->resolveWindowForDate($referenceDate);

        $filterTeamIds = null;

        if ($q !== null) {
            try {
                $searchResult = $typesenseTeams->searchTeamIdsForRanking($q, $sportId);
                $filterTeamIds = $searchResult['ids'];
            } catch (TypesenseClientError $e) {
                return response()->json([
                    'message' => __('Recherche classement indisponible pour le moment.'),
                    'error' => $e->getMessage(),
                ], 502);
            }

            if ($filterTeamIds === []) {
                return response()->json([
                    'data' => [
                        'sport_id' => $sportId,
                        'year' => $year,
                        "region" => $region,
                        'season_key' => $seasonWindow->key,
                        'rankings' => [],
                        'pagination' => [
                            'current_page' => $page,
                            'per_page' => $perPage,
                            'has_more' => false,
                        ],
                    ],
                ]);
            }
        }

        $rankings = $statsRepository->loadSportRanking($sportId, $region, $seasonWindow, $page, $perPage, $filterTeamIds);
        $hasMore = $statsRepository->loadSportRanking($sportId, $region, $seasonWindow, $page + 1, $perPage, $filterTeamIds) !== [];

        $userTeamIds = DB::table('team_members')
            ->where('user_id', (int) $request->user()->id)
            ->where('status', 'active')
            ->pluck('team_id')
            ->map(static fn($id): int => (int) $id)
            ->all();

        $rankings = array_map(
            static fn(array $row): array => $row + [
                'is_current_user_team' => in_array($row['team_id'], $userTeamIds, true),
            ],
            $rankings,
        );

        return response()->json([
            'data' => [
                'sport_id' => $sportId,
                "region" => $region,
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
