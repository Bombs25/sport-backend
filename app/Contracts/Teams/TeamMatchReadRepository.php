<?php

namespace App\Contracts\Teams;

/**
 * Lecture des événements match via Query Builder (cf. §1.7 schema).
 */
interface TeamMatchReadRepository
{
    /**
     * Dernier match terminé avec score validé impliquant l'équipe.
     *
     * @return array{
     *     match_event_id: int,
     *     match_result_id: int,
     *     home_team_id: int,
     *     away_team_id: int,
     *     home_team_name: string,
     *     away_team_name: string,
     *     home_team_logo_url: ?string,
     *     away_team_logo_url: ?string,
     *     home_score: int,
     *     away_score: int,
     *     validated_at: ?string
     * }|null
     */
    public function findLatestValidatedMatchForTeam(int $teamId): ?array;

    /**
     * Matchs terminés à score validé pour l'équipe, du plus récent au plus ancien.
     *
     * @return list<array{
     *     match_event_id: int,
     *     match_result_id: int,
     *     home_team_id: int,
     *     away_team_id: int,
     *     home_team_name: string,
     *     away_team_name: string,
     *     home_score: int,
     *     away_score: int,
     *     validated_at: ?string,
     *     scheduled_at: ?string,
     *     venue: ?string
     * }>
     */
    public function listValidatedMatchesForTeam(int $teamId, int $limit = 50): array;
}
