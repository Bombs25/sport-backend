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
}
