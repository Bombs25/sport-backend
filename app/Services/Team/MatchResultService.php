<?php

namespace App\Services\Team;

use App\Jobs\Team\ApplyValidatedMatchResultStatsJob;
use App\Support\PublicImageUrl;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Cycle score : soumission et 1re évaluation par l’équipe à domicile, réponse (validation ou refus) par l’away.
 *
 * Cohérence `match_events.status` / `match_results.status` :
 * — Tant que le score n’est pas validé par l’away, l’événement reste `scheduled`
 *   (y compris résultat `score_pending_validation` ou `refused` ; un refus ne met pas l’événement en `finished`).
 * — Sur validation adverse ({@see respondToMatchResult}), le résultat passe à `validated` et l’événement à `finished`.
 */
class MatchResultService
{
    /** @var list<string> */
    public const OPEN_DISPUTE_STATUSES = ['pending', 'under_review'];

    public function resolvePublicMatchStatus(string $matchEventStatus, ?string $matchResultStatus, bool $hasOpenDispute): string
    {
        if ($hasOpenDispute) {
            return 'disputed';
        }

        if ($matchResultStatus === 'score_pending_validation') {
            return 'scores_to_confirm';
        }

        if ($matchResultStatus === 'refused') {
            return 'score_refused';
        }

        if ($matchResultStatus === 'validated' || $matchEventStatus === 'finished') {
            return 'finished';
        }

        return match ($matchEventStatus) {
            'requested' => 'pending',
            'scheduled' => 'accepted',
            'cancelled' => 'refused',
            default => $matchEventStatus,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function getMatchResultDetail(int $matchEventId, int $actorUserId): array
    {
        $match = $this->loadMatchEventOrFail($matchEventId);
        $homeTeamId = (int) $match->home_team_id;
        $awayTeamId = (int) $match->away_team_id;

        if (! $this->userManagesTeam($homeTeamId, $actorUserId) && ! $this->userManagesTeam($awayTeamId, $actorUserId)) {
            throw new AuthorizationException(__('Vous ne participez pas à ce match.'));
        }

        $result = DB::table('match_results')
            ->where('match_event_id', $matchEventId)
            ->orderByDesc('id')
            ->first();

        $dispute = $result !== null
            ? DB::table('match_result_disputes')
                ->where('match_result_id', $result->id)
                ->orderByDesc('id')
                ->first()
            : null;

        $hasOpenDispute = $dispute !== null && in_array($dispute->status, self::OPEN_DISPUTE_STATUSES, true);

        $homeTeam = DB::table('teams')->where('id', $homeTeamId)->select(['id', 'name', 'logo_url'])->first();
        $awayTeam = DB::table('teams')->where('id', $awayTeamId)->select(['id', 'name', 'logo_url'])->first();
        $sport = DB::table('sports')
            ->join('teams', 'teams.sport_id', '=', 'sports.id')
            ->where('teams.id', $homeTeamId)
            ->select(['sports.name as sport_name'])
            ->first();

        $publicStatus = $this->resolvePublicMatchStatus(
            (string) $match->status,
            $result?->status,
            $hasOpenDispute,
        );

        $direction = $this->userManagesTeam($awayTeamId, $actorUserId) ? 'received' : 'sent';

        return [
            'match_event_id' => $matchEventId,
            'direction' => $direction,
            'status' => $publicStatus,
            'scheduled_at' => $match->scheduled_at,
            'venue' => $match->venue,
            'notes' => $match->notes,
            'home_team' => [
                'id' => $homeTeamId,
                'name' => $homeTeam?->name,
                'logo_url' => PublicImageUrl::from($homeTeam?->logo_url ?? null),
            ],
            'away_team' => [
                'id' => $awayTeamId,
                'name' => $awayTeam?->name,
                'logo_url' => PublicImageUrl::from($awayTeam?->logo_url ?? null),
            ],
            'sport' => ['name' => $sport?->sport_name ?? ''],
            'result' => $result === null ? null : [
                'match_result_id' => (int) $result->id,
                'home_score' => (int) $result->home_score,
                'away_score' => (int) $result->away_score,
                'status' => $result->status,
                'refusal_reason' => $result->refusal_reason,
                'submitted_at' => $result->submitted_at,
                'responded_at' => $result->responded_at,
            ],
            'dispute' => $dispute === null ? null : [
                'match_result_dispute_id' => (int) $dispute->id,
                'status' => $dispute->status,
                'dispute_reason_score_incorrect' => (bool) $dispute->dispute_reason_score_incorrect,
                'dispute_reason_fair_play' => (bool) $dispute->dispute_reason_fair_play,
                'dispute_reason_behavior' => (bool) $dispute->dispute_reason_behavior,
                'details' => $dispute->details,
                'evidence_url' => $dispute->evidence_path !== null
                    ? PublicImageUrl::from($dispute->evidence_path)
                    : null,
                'resolution_notes' => $dispute->resolution_notes,
                'resolved_at' => $dispute->resolved_at,
                'created_at' => $dispute->created_at,
            ],
            'has_open_dispute' => $hasOpenDispute,
        ];
    }

    /**
     * @param  array{resolution: string, resolution_notes?: string|null, home_score?: int|null, away_score?: int|null}  $payload
     */
    public function resolveDispute(int $matchResultDisputeId, int $actorUserId, array $payload): void
    {
        DB::transaction(function () use ($matchResultDisputeId, $actorUserId, $payload): void {
            $dispute = DB::table('match_result_disputes')->where('id', $matchResultDisputeId)->first();
            if ($dispute === null) {
                throw ValidationException::withMessages([
                    'match_result_dispute_id' => __('Litige introuvable.'),
                ]);
            }

            if (! in_array($dispute->status, self::OPEN_DISPUTE_STATUSES, true)) {
                throw ValidationException::withMessages([
                    'match_result_dispute_id' => __('Ce litige est déjà clos.'),
                ]);
            }

            $result = DB::table('match_results')->where('id', $dispute->match_result_id)->first();
            if ($result === null) {
                throw ValidationException::withMessages([
                    'match_result_dispute_id' => __('Résultat introuvable.'),
                ]);
            }

            $match = $this->loadMatchEventOrFail((int) $result->match_event_id);
            $homeTeamId = (int) $match->home_team_id;
            $awayTeamId = (int) $match->away_team_id;

            if (! $this->userManagesTeam($homeTeamId, $actorUserId) && ! $this->userManagesTeam($awayTeamId, $actorUserId)) {
                throw new AuthorizationException(__('Vous ne pouvez pas trancher ce litige.'));
            }

            $resolution = $payload['resolution'];
            $notes = isset($payload['resolution_notes']) ? trim((string) $payload['resolution_notes']) : null;
            $now = now();

            if ($resolution === 'under_review') {
                DB::table('match_result_disputes')->where('id', $dispute->id)->update([
                    'status' => 'under_review',
                    'moderator_user_id' => $actorUserId,
                    'moderator_notes' => $notes,
                    'updated_at' => $now,
                ]);

                return;
            }

            if ($resolution === 'resolved_away') {
                $homeScore = $payload['home_score'] ?? null;
                $awayScore = $payload['away_score'] ?? null;
                if ($homeScore === null || $awayScore === null) {
                    throw ValidationException::withMessages([
                        'home_score' => __('Les scores arbitrés sont obligatoires.'),
                    ]);
                }

                $this->forceValidateResult(
                    $match,
                    $result,
                    $actorUserId,
                    (int) $homeScore,
                    (int) $awayScore,
                );

                DB::table('match_result_disputes')->where('id', $dispute->id)->update([
                    'status' => 'resolved_away',
                    'moderator_user_id' => $actorUserId,
                    'resolution_notes' => $notes,
                    'resolved_at' => $now,
                    'updated_at' => $now,
                ]);

                return;
            }

            if ($resolution === 'resolved_home' || $resolution === 'dismissed') {
                DB::table('match_result_disputes')->where('id', $dispute->id)->update([
                    'status' => $resolution,
                    'moderator_user_id' => $actorUserId,
                    'resolution_notes' => $notes,
                    'resolved_at' => $now,
                    'updated_at' => $now,
                ]);

                return;
            }

            throw ValidationException::withMessages([
                'resolution' => __('Résolution invalide.'),
            ]);
        });
    }

    /**
     * @param  int  $actorTeamId  Doit être `home_team_id` du match (équipe demanderesse / domicile) — seul son capitaine ou créateur peut soumettre.
     * @param  int  $actorUserId  Utilisateur authentifié (capitaine ou créateur de `$actorTeamId`).
     * @return array{created: bool, match_result_id: int}
     */
    public function submitScoreAndFirstEvaluation(
        int $matchEventId,
        int $actorTeamId,
        int $actorUserId,
        int $homeScore,
        int $awayScore,
        int $fairPlayRating,
        int $punctualityRating,
        ?string $remarks,
    ): array {
        $this->assertUserManagesTeam($actorTeamId, $actorUserId);

        return DB::transaction(function () use (
            $matchEventId,
            $actorTeamId, // home_team_id (demandeur), seul rôle autorisé à soumettre
            $actorUserId, // auteur de la soumission, déjà autorisé sur cette équipe
            $homeScore,
            $awayScore,
            $fairPlayRating,
            $punctualityRating,
            $remarks,
        ): array {
            // Vérifie l’existence du match, l’appartenance de l’équipe et la règle produit :
            // seul `home_team_id` (demandeur) peut soumettre le score initial.
            $match = $this->loadMatchEventOrFail($matchEventId);
            $this->assertTeamPlaysMatch($match, $actorTeamId);
            $this->assertHomeTeamSubmitsScore($match, $actorTeamId);

            $homeTeamId = (int) $match->home_team_id;
            $awayTeamId = (int) $match->away_team_id;

            $existing = DB::table('match_results')
                ->where('match_event_id', $matchEventId)
                ->first();

            // Étape 1 — Premier envoi (home_team_id) : crée le résultat + la 1re note (home → away).
            if ($existing === null) {
                if ($match->status !== 'scheduled') {
                    throw ValidationException::withMessages([
                        'match_event_id' => __('Le match doit être confirmé avant d’enregistrer un score.'),
                    ]);
                }

                $now = now();
                $matchResultId = (int) DB::table('match_results')->insertGetId([
                    'match_event_id' => $matchEventId,
                    'home_score' => $homeScore,
                    'away_score' => $awayScore,
                    'status' => 'score_pending_validation',
                    'submitted_by_user_id' => $actorUserId,
                    'submitted_at' => $now,
                    'responded_by_user_id' => null,
                    'responded_at' => null,
                    'validated_at' => null,
                    'refusal_reason' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('match_opponent_evaluations')->insert([
                    'match_result_id' => $matchResultId,
                    'evaluator_team_id' => $actorTeamId,
                    'evaluator_user_id' => $actorUserId,
                    'evaluated_team_id' => $awayTeamId,
                    'fair_play_rating' => $fairPlayRating,
                    'punctuality_rating' => $punctualityRating,
                    'remarks' => $remarks,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                return ['created' => true, 'match_result_id' => $matchResultId];
            }

            // Étape 2 — Tant que le score est en attente : le home peut ajuster score + 1re note.
            if ($existing->status === 'score_pending_validation') {
                if ($actorTeamId !== $homeTeamId) {
                    throw new AuthorizationException(__("Seule l'équipe à domicile (demandeur du match) peut modifier le score tant qu'il n'est pas validé."));
                }

                $now = now();
                $this->updateMatchResultForHomeSubmission(
                    (int) $existing->id,
                    $actorUserId,
                    $homeScore,
                    $awayScore,
                    $now,
                    null,
                );
                $this->updateHomeOpponentEvaluation(
                    (int) $existing->id,
                    $actorTeamId,
                    $actorUserId,
                    $awayTeamId,
                    $fairPlayRating,
                    $punctualityRating,
                    $remarks,
                    $now,
                );

                return ['created' => false, 'match_result_id' => (int) $existing->id];
            }

            // Étape 3 — Après refus (et sans litige ouvert) : le home peut re-soumettre et repasser en attente.
            if ($existing->status === 'refused') {
                if ($actorTeamId !== $homeTeamId) {
                    throw new AuthorizationException(__("Seule l'équipe à domicile dont le score a été refusé peut proposer un nouveau score."));
                }
                if ($this->matchResultHasOpenDispute((int) $existing->id)) {
                    throw ValidationException::withMessages([
                        'match_event_id' => __('Un litige est en cours sur ce résultat.'),
                    ]);
                }

                $now = now();
                $this->updateMatchResultForHomeSubmission(
                    (int) $existing->id,
                    $actorUserId,
                    $homeScore,
                    $awayScore,
                    $now,
                    'score_pending_validation',
                );
                $this->updateHomeOpponentEvaluation(
                    (int) $existing->id,
                    $actorTeamId,
                    $actorUserId,
                    $awayTeamId,
                    $fairPlayRating,
                    $punctualityRating,
                    $remarks,
                    $now,
                );

                return ['created' => false, 'match_result_id' => (int) $existing->id];
            }

            throw ValidationException::withMessages([
                'match_event_id' => __('Ce résultat ne peut plus être modifié.'),
            ]);
        });
    }

    /**
     * Réponse au score : uniquement le capitaine / créateur de l’équipe **away** (`away_team_id`, receveur de la demande).
     *
     * @param  array{decision: string, refusal_reason?: string|null, fair_play_rating?: int|null, punctuality_rating?: int|null, remarks?: string|null}  $payload
     */
    public function respondToMatchResult(int $matchEventId, int $actorUserId, array $payload): void
    {
        DB::transaction(function () use ($matchEventId, $actorUserId, $payload): void {
            $match = $this->loadMatchEventOrFail($matchEventId);
            $result = DB::table('match_results')
                ->where('match_event_id', $matchEventId)
                ->first();

            if ($result === null || $result->status !== 'score_pending_validation') {
                throw ValidationException::withMessages([
                    'match_event_id' => __('Aucun score en attente de validation pour ce match.'),
                ]);
            }

            $homeTeamId = (int) $match->home_team_id;
            $awayTeamId = (int) $match->away_team_id;

            if (! $this->userManagesTeam($homeTeamId, (int) $result->submitted_by_user_id)) {
                throw ValidationException::withMessages([
                    'match_event_id' => __('État du résultat incohérent : le soumissionnaire doit être l’équipe à domicile.'),
                ]);
            }

            $this->assertUserManagesTeam($awayTeamId, $actorUserId);

            $decision = $payload['decision'];
            $now = now();

            if ($decision === 'refuse') {
                $reason = $payload['refusal_reason'] ?? null;
                if ($reason === null || trim((string) $reason) === '') {
                    throw ValidationException::withMessages([
                        'refusal_reason' => __('Un motif de refus est obligatoire.'),
                    ]);
                }

                DB::table('match_results')
                    ->where('id', $result->id)
                    ->update([
                        'status' => 'refused',
                        'responded_by_user_id' => $actorUserId,
                        'responded_at' => $now,
                        'refusal_reason' => $reason,
                        'validated_at' => null,
                        'updated_at' => $now,
                    ]);

                return;
            }

            if ($decision !== 'validate') {
                throw ValidationException::withMessages([
                    'decision' => __('Décision invalide.'),
                ]);
            }

            $fairPlay = $payload['fair_play_rating'] ?? null;
            $punctuality = $payload['punctuality_rating'] ?? null;
            if ($fairPlay === null || $punctuality === null) {
                throw ValidationException::withMessages([
                    'fair_play_rating' => __('Les notes fair-play et ponctualité sont obligatoires pour valider.'),
                ]);
            }

            $secondExists = DB::table('match_opponent_evaluations')
                ->where('match_result_id', $result->id)
                ->where('evaluator_team_id', $awayTeamId)
                ->exists();

            if ($secondExists) {
                throw ValidationException::withMessages([
                    'match_event_id' => __('Ce score a déjà été validé.'),
                ]);
            }

            DB::table('match_opponent_evaluations')->insert([
                'match_result_id' => $result->id,
                'evaluator_team_id' => $awayTeamId,
                'evaluator_user_id' => $actorUserId,
                'evaluated_team_id' => $homeTeamId,
                'fair_play_rating' => $fairPlay,
                'punctuality_rating' => $punctuality,
                'remarks' => $payload['remarks'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('match_results')
                ->where('id', $result->id)
                ->update([
                    'status' => 'validated',
                    'responded_by_user_id' => $actorUserId,
                    'responded_at' => $now,
                    'validated_at' => $now,
                    'refusal_reason' => null,
                    'updated_at' => $now,
                ]);

            DB::table('match_events')
                ->where('id', $matchEventId)
                ->update([
                    'status' => 'finished',
                    'updated_at' => $now,
                ]);

            $homeScore = (int) $result->home_score;
            $awayScore = (int) $result->away_score;
            DB::afterCommit(static function () use ($homeTeamId, $awayTeamId, $homeScore, $awayScore): void {
                ApplyValidatedMatchResultStatsJob::dispatchSync(
                    $homeTeamId,
                    $awayTeamId,
                    $homeScore,
                    $awayScore,
                );
            });
        });
    }

    /**
     * Litige : uniquement l’équipe **away** (celle qui a pu refuser le score).
     *
     * @param  array{dispute_reason_score_incorrect: bool, dispute_reason_fair_play: bool, dispute_reason_behavior: bool, details: string}  $payload
     */
    public function openDispute(
        int $matchEventId,
        int $actorUserId,
        array $payload,
        ?string $evidencePath,
        ?string $evidenceDisk,
    ): int {
        return DB::transaction(function () use ($matchEventId, $actorUserId, $payload, $evidencePath, $evidenceDisk): int {
            $match = $this->loadMatchEventOrFail($matchEventId);
            $result = DB::table('match_results')
                ->where('match_event_id', $matchEventId)
                ->first();

            if ($result === null || $result->status !== 'refused') {
                throw ValidationException::withMessages([
                    'match_event_id' => __('Un litige n’est possible qu’après refus du score.'),
                ]);
            }

            if (! $this->userManagesTeam((int) $match->home_team_id, (int) $result->submitted_by_user_id)) {
                throw ValidationException::withMessages([
                    'match_event_id' => __('État du résultat incohérent : le soumissionnaire doit être l’équipe à domicile.'),
                ]);
            }

            $this->assertUserManagesTeam((int) $match->away_team_id, $actorUserId);

            if (! $payload['dispute_reason_score_incorrect'] && ! $payload['dispute_reason_fair_play'] && ! $payload['dispute_reason_behavior']) {
                throw ValidationException::withMessages([
                    'dispute_reason_score_incorrect' => __('Sélectionnez au moins un motif de litige.'),
                ]);
            }

            if ($this->matchResultHasOpenDispute((int) $result->id)) {
                throw ValidationException::withMessages([
                    'match_event_id' => __('Un litige est déjà ouvert pour ce résultat.'),
                ]);
            }

            $now = now();

            return (int) DB::table('match_result_disputes')->insertGetId([
                'match_result_id' => $result->id,
                'opened_by_user_id' => $actorUserId,
                'dispute_reason_score_incorrect' => $payload['dispute_reason_score_incorrect'],
                'dispute_reason_fair_play' => $payload['dispute_reason_fair_play'],
                'dispute_reason_behavior' => $payload['dispute_reason_behavior'],
                'details' => $payload['details'],
                'evidence_path' => $evidencePath,
                'evidence_disk' => $evidenceDisk,
                'status' => 'pending',
                'moderator_user_id' => null,
                'moderator_notes' => null,
                'resolution_notes' => null,
                'resolved_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    private function loadMatchEventOrFail(int $matchEventId): object
    {
        $match = DB::table('match_events')->where('id', $matchEventId)->first();
        if ($match === null) {
            throw ValidationException::withMessages([
                'match_event_id' => __('Match introuvable.'),
            ]);
        }

        return $match;
    }

    private function assertTeamPlaysMatch(object $match, int $teamId): void
    {
        $home = (int) $match->home_team_id;
        $away = (int) $match->away_team_id;
        if ($teamId !== $home && $teamId !== $away) {
            throw ValidationException::withMessages([
                'team_id' => __('Cette équipe ne participe pas à ce match.'),
            ]);
        }
    }

    /**
     * Mise à jour du score par l'équipe à domicile (home_team_id).
     *
     * @param  'score_pending_validation'|null  $status
     */
    private function updateMatchResultForHomeSubmission(
        int $matchResultId,
        int $actorUserId,
        int $homeScore,
        int $awayScore,
        mixed $now,
        ?string $status,
    ): void {
        $update = [
            'home_score' => $homeScore,
            'away_score' => $awayScore,
            'submitted_by_user_id' => $actorUserId,
            'submitted_at' => $now,
            'updated_at' => $now,
        ];

        if ($status !== null) {
            $update['status'] = $status;
            $update['responded_by_user_id'] = null;
            $update['responded_at'] = null;
            $update['validated_at'] = null;
            $update['refusal_reason'] = null;
        }

        DB::table('match_results')
            ->where('id', $matchResultId)
            ->update($update);
    }

    private function updateHomeOpponentEvaluation(
        int $matchResultId,
        int $actorTeamId,
        int $actorUserId,
        int $awayTeamId,
        int $fairPlayRating,
        int $punctualityRating,
        ?string $remarks,
        mixed $now,
    ): void {
        DB::table('match_opponent_evaluations')
            ->where('match_result_id', $matchResultId)
            ->where('evaluator_team_id', $actorTeamId)
            ->update([
                'evaluator_user_id' => $actorUserId,
                'evaluated_team_id' => $awayTeamId,
                'fair_play_rating' => $fairPlayRating,
                'punctuality_rating' => $punctualityRating,
                'remarks' => $remarks,
                'updated_at' => $now,
            ]);
    }

    /**
     * Aligné sur la demande de match : `home_team_id` = demandeur (route POST match-requests), seul lui soumet le premier score.
     *
     * @throws AuthorizationException
     */
    private function assertHomeTeamSubmitsScore(object $match, int $actorTeamId): void
    {
        if ((int) $actorTeamId !== (int) $match->home_team_id) {
            throw new AuthorizationException(__('Seul le capitaine ou le créateur de l’équipe à domicile (demandeur du match) peut envoyer le score et la première évaluation.'));
        }
    }

    /**
     * @throws AuthorizationException
     */
    private function assertUserManagesTeam(int $teamId, int $actorUserId): void
    {
        if (! $this->userManagesTeam($teamId, $actorUserId)) {
            throw new AuthorizationException(__("Y'a que le createur ou le capitaine de l'equipe qui peuvent demander un match,modifier ou annuler une demande de match"));
        }
    }

    private function userManagesTeam(int $teamId, int $actorUserId): bool
    {
        $team = DB::table('teams')->where('id', $teamId)->select(['id', 'creator_id'])->first();
        if ($team === null) {
            return false;
        }
        if ((int) $team->creator_id === (int) $actorUserId) {
            return true;
        }

        return DB::table('team_members')
            ->where('team_id', $teamId)
            ->where('user_id', (int) $actorUserId)
            ->where('status', 'active')
            ->where('role', 'captain')
            ->exists();
    }

    private function matchResultHasOpenDispute(int $matchResultId): bool
    {
        return DB::table('match_result_disputes')
            ->where('match_result_id', $matchResultId)
            ->whereIn('status', self::OPEN_DISPUTE_STATUSES)
            ->exists();
    }

    private function forceValidateResult(object $match, object $result, int $actorUserId, int $homeScore, int $awayScore): void
    {
        $matchEventId = (int) $match->id;
        $homeTeamId = (int) $match->home_team_id;
        $awayTeamId = (int) $match->away_team_id;
        $now = now();

        DB::table('match_results')
            ->where('id', $result->id)
            ->update([
                'home_score' => $homeScore,
                'away_score' => $awayScore,
                'status' => 'validated',
                'responded_by_user_id' => $actorUserId,
                'responded_at' => $now,
                'validated_at' => $now,
                'refusal_reason' => null,
                'updated_at' => $now,
            ]);

        DB::table('match_events')
            ->where('id', $matchEventId)
            ->update([
                'status' => 'finished',
                'updated_at' => $now,
            ]);

        ApplyValidatedMatchResultStatsJob::dispatchSync(
            $homeTeamId,
            $awayTeamId,
            $homeScore,
            $awayScore,
        );
    }
}
