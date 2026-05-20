<?php

namespace App\Contracts\Stats;

use App\Services\Stats\SeasonWindow;

interface StatsRepository
{
    public function incrementTeamStats(
        int $teamId,
        int $sportId,
        SeasonWindow $seasonWindow,
        string $counterColumn,
        int $pointDelta,
        mixed $now,
    ): void;

    /**
     * @param  array<int, int>  $teamIds
     * @return array<int, array{team_id: int, team_name: string, point_count: int, rank: int|null}>
     */
    public function loadTeamSnapshots(int $sportId, SeasonWindow $seasonWindow, array $teamIds): array;

    public function maxPointCount(int $sportId, SeasonWindow $seasonWindow): int;

    /**
     * Charge le classement complet d'un sport sur la fenetre de saison fournie, ordonne par rang.
     *
     * @return array<int, array{rank: int, team_id: int, team_name: string, logo_url: ?string,
     *     victory_count: int, draw_count: int, defeat_count: int, point_count: int}>
     */
    /**
     * @param  list<int>|null  $filterTeamIds  Sous-ensemble d'équipes (ex. IDs Typesense) ; null = classement complet.
     */
    public function loadSportRanking(
        int $sportId,
        SeasonWindow $seasonWindow,
        int $page = 1,
        int $perPage = 10,
        ?array $filterTeamIds = null,
    ): array;

    /**
     * @return array<int, int>
     */
    public function loadAvailableRankingYears(int $sportId): array;

    /**
     * Agrège les lignes stats d'une équipe sur la fenêtre saison (sums des compteurs).
     *
     * @return array{
     *     played: int,
     *     won: int,
     *     lost: int,
     *     draw: int,
     *     point_count: int
     * }
     */
    public function loadTeamSeasonStats(
        int $teamId,
        int $sportId,
        SeasonWindow $seasonWindow,
    ): array;
}
