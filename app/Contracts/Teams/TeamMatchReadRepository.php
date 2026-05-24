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
     * Page de matchs terminés à score validé pour l'équipe, du plus récent au plus
     * ancien (10 par page).
     *
     * @return array{
     *     matches: list<array{
     *         match_event_id: int,
     *         match_result_id: int,
     *         home_team_id: int,
     *         away_team_id: int,
     *         home_team_name: string,
     *         away_team_name: string,
     *         home_team_logo_url: ?string,
     *         away_team_logo_url: ?string,
     *         home_score: int,
     *         away_score: int,
     *         total_likes: int,
     *         total_comments: int,
     *         submitted_by_user_id: int,
     *         validated_at: ?string,
     *         scheduled_at: ?string,
     *         venue: ?string
     *     }>,
     *     meta: array{
     *         current_page: int,
     *         per_page: int,
     *         total: int,
     *         last_page: int,
     *         has_more: bool
     *     }
     * }
     */
    public function listValidatedMatchesForTeam(int $teamId, int $page = 1): array;
}
