<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Team\MatchResultService;
use Carbon\CarbonInterface;
use Database\Seeders\Support\DemoPassword;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Rattache le jeu « équipes génériques » ({@see DemoTeamsSeeder}) au domaine match : événements planifiés,
 * résultats validés ou en attente, évaluations, commentaires et likes sur une publication distincte de
 * {@see DemoMatchSocialInteractionsSeeder}.
 *
 * Aligné sur {@see MatchResultService} : résultat en attente ou refusé ⇒ événement `scheduled` ;
 * résultat validé ⇒ événement `finished`.
 *
 * Idempotent : ignoré si un {@see self::NOTES_MARKER} est déjà présent sur {@see match_events}.
 */
class DemoMatchCalendarSeeder extends Seeder
{
    private const NOTES_MARKER = '__demo_calendar__';

    private const DEMO_LOGIN_EMAIL = 'calendar.demo@osport.local';

    public function run(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('match_events')) {
            return;
        }

        if (DB::table('match_events')->where('notes', self::NOTES_MARKER)->exists()) {
            if ($this->command !== null) {
                $this->command->info('DemoMatchCalendarSeeder : déjà présent, ignoré.');
            }

            return;
        }

        $teams = DB::table('teams')
            ->whereIn('slug', [
                'demo-seed-team-01',
                'demo-seed-team-02',
                'demo-seed-team-03',
                'demo-seed-team-04',
                'demo-seed-team-05',
            ])
            ->orderBy('slug')
            ->get(['id', 'slug'])
            ->keyBy('slug');

        if ($teams->count() < 2) {
            return;
        }

        $now = now();
        $t1 = $teams->get('demo-seed-team-01');
        $t2 = $teams->get('demo-seed-team-02');
        $t3 = $teams->get('demo-seed-team-03');
        $t4 = $teams->get('demo-seed-team-04');
        $t5 = $teams->get('demo-seed-team-05');

        if ($t1 !== null && $t2 !== null) {
            $this->insertScheduledEvent((int) $t1->id, (int) $t2->id, $now->copy()->addDays(7), $now);
        }

        if ($t1 !== null && $t3 !== null) {
            $this->insertScheduledEvent((int) $t1->id, (int) $t3->id, $now->copy()->addDays(14), $now);
        }

        if ($t2 !== null && $t4 !== null) {
            $eventId = $this->insertFinishedEvent((int) $t2->id, (int) $t4->id, $now->copy()->subDays(5), $now);
            $homeId = (int) $t2->id;
            $awayId = (int) $t4->id;
            $homeCaptain = $this->resolveCaptainUserId($homeId);
            $awayCaptain = $this->resolveCaptainUserId($awayId);
            if ($homeCaptain !== null && $awayCaptain !== null) {
                $resultId = $this->insertValidatedResult($eventId, $homeCaptain, $awayCaptain, $now);
                $this->insertEvaluationPair($resultId, $homeId, $awayId, $homeCaptain, $awayCaptain, $now);
                $this->seedLightPublicationInteractions($resultId, $homeCaptain, $awayCaptain, $now);
            }
        }

        if ($t5 !== null && $t1 !== null) {
            $eventId = $this->insertScheduledEvent(
                (int) $t5->id,
                (int) $t1->id,
                $now->copy()->subDays(8),
                $now,
                'Terrain démo — score en attente de validation',
            );
            $homeCaptain = $this->resolveCaptainUserId((int) $t5->id);
            if ($homeCaptain !== null) {
                $resultId = $this->insertPendingResult($eventId, $homeCaptain, $now);
                $this->insertFirstEvaluationOnly(
                    $resultId,
                    (int) $t5->id,
                    (int) $t1->id,
                    $homeCaptain,
                    $now,
                );
            }
        }

        if ($t2 !== null) {
            $this->ensureDemoLoginForTeamCaptain((int) $t2->id, $now);
        }

        if ($this->command !== null) {
            $this->command->info('DemoMatchCalendarSeeder : calendrier + résultats (équipes demo-seed-team-01…05).');
            $this->command->info('Compte API optionnel : '.self::DEMO_LOGIN_EMAIL.' / '.DemoPassword::PLAIN.' (token demo-calendar-seed).');
        }
    }

    private function insertScheduledEvent(
        int $homeTeamId,
        int $awayTeamId,
        CarbonInterface $scheduledAt,
        CarbonInterface $now,
        ?string $venue = null,
    ): int {
        return (int) DB::table('match_events')->insertGetId([
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'scheduled_at' => $scheduledAt,
            'venue' => $venue ?? 'Stade démo — calendrier',
            'status' => 'scheduled',
            'notes' => self::NOTES_MARKER,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function insertFinishedEvent(int $homeTeamId, int $awayTeamId, CarbonInterface $scheduledAt, CarbonInterface $now): int
    {
        return (int) DB::table('match_events')->insertGetId([
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'scheduled_at' => $scheduledAt,
            'venue' => 'Terrain démo — terminé',
            'status' => 'finished',
            'notes' => self::NOTES_MARKER,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function insertValidatedResult(
        int $matchEventId,
        int $submittedByUserId,
        int $respondedByUserId,
        CarbonInterface $now,
    ): int {
        return (int) DB::table('match_results')->insertGetId([
            'match_event_id' => $matchEventId,
            'home_score' => 2,
            'away_score' => 1,
            'total_comments' => 0,
            'total_likes' => 0,
            'status' => 'validated',
            'submitted_by_user_id' => $submittedByUserId,
            'submitted_at' => $now->copy()->subHours(6),
            'responded_by_user_id' => $respondedByUserId,
            'responded_at' => $now->copy()->subHours(3),
            'validated_at' => $now->copy()->subHours(1),
            'refusal_reason' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function insertPendingResult(int $matchEventId, int $submittedByUserId, CarbonInterface $now): int
    {
        return (int) DB::table('match_results')->insertGetId([
            'match_event_id' => $matchEventId,
            'home_score' => 0,
            'away_score' => 0,
            'total_comments' => 0,
            'total_likes' => 0,
            'status' => 'score_pending_validation',
            'submitted_by_user_id' => $submittedByUserId,
            'submitted_at' => $now->copy()->subDay(),
            'responded_by_user_id' => null,
            'responded_at' => null,
            'validated_at' => null,
            'refusal_reason' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function insertEvaluationPair(
        int $matchResultId,
        int $homeTeamId,
        int $awayTeamId,
        int $homeCaptainId,
        int $awayCaptainId,
        CarbonInterface $now,
    ): void {
        DB::table('match_opponent_evaluations')->insert([
            [
                'match_result_id' => $matchResultId,
                'evaluator_team_id' => $homeTeamId,
                'evaluator_user_id' => $homeCaptainId,
                'evaluated_team_id' => $awayTeamId,
                'fair_play_rating' => 5,
                'punctuality_rating' => 4,
                'remarks' => 'Calendrier démo — équipe domicile.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'match_result_id' => $matchResultId,
                'evaluator_team_id' => $awayTeamId,
                'evaluator_user_id' => $awayCaptainId,
                'evaluated_team_id' => $homeTeamId,
                'fair_play_rating' => 4,
                'punctuality_rating' => 5,
                'remarks' => 'Calendrier démo — équipe extérieur.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    private function insertFirstEvaluationOnly(
        int $matchResultId,
        int $evaluatorTeamId,
        int $evaluatedTeamId,
        int $evaluatorUserId,
        CarbonInterface $now,
    ): void {
        DB::table('match_opponent_evaluations')->insert([
            'match_result_id' => $matchResultId,
            'evaluator_team_id' => $evaluatorTeamId,
            'evaluator_user_id' => $evaluatorUserId,
            'evaluated_team_id' => $evaluatedTeamId,
            'fair_play_rating' => 4,
            'punctuality_rating' => 4,
            'remarks' => 'Score soumis — en attente de l’adversaire.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function seedLightPublicationInteractions(
        int $publicationId,
        int $userA,
        int $userB,
        CarbonInterface $now,
    ): void {
        $pub = 'automatic';

        $c1 = (int) DB::table('comments')->insertGetId([
            'content' => 'Bel échange sur ce match de calendrier démo.',
            'publication_id' => $publicationId,
            'publication_type' => $pub,
            'user_id' => $userA,
            'responses_count' => 1,
            'likes_count' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('comments')->insert([
            'content' => 'Hâte de rejouer contre vous.',
            'publication_id' => $publicationId,
            'publication_type' => $pub,
            'user_id' => $userB,
            'responses_count' => 0,
            'likes_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $handleRow = DB::table('user_profiles')->where('user_id', $userA)->value('handle');
        $handle = $handleRow !== null ? (string) $handleRow : 'joueur_cal';

        $r1 = (int) DB::table('response_commentaires')->insertGetId([
            'comment_id' => $c1,
            'is_reponse_to_main_comment' => true,
            'response' => '+1, bon match.',
            'responded_to_who' => $handle,
            'users_id' => $userB,
            'likes_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('comments_likes')->insert([
            'users_id' => $userB,
            'comment_id' => $c1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('response_comment_like')->insert([
            'user_id' => $userA,
            'responses_comment_id' => $r1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('response_commentaires')->where('id', $r1)->update([
            'likes_count' => 1,
            'updated_at' => $now,
        ]);

        DB::table('post_likes')->insert([
            'users_id' => $userB,
            'publication_id' => $publicationId,
            'publication_type' => $pub,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('match_results')->where('id', $publicationId)->update([
            'total_comments' => 3,
            'total_likes' => 1,
            'updated_at' => $now,
        ]);
    }

    private function resolveCaptainUserId(int $teamId): ?int
    {
        $id = DB::table('team_members')
            ->where('team_id', $teamId)
            ->where('role', 'captain')
            ->where('status', 'active')
            ->value('user_id');

        if ($id !== null) {
            return (int) $id;
        }

        $fallback = DB::table('team_members')
            ->where('team_id', $teamId)
            ->where('status', 'active')
            ->orderBy('id')
            ->value('user_id');

        return $fallback !== null ? (int) $fallback : null;
    }

    private function ensureDemoLoginForTeamCaptain(int $teamId, CarbonInterface $now): void
    {
        $captainId = $this->resolveCaptainUserId($teamId);
        if ($captainId === null) {
            return;
        }

        DB::table('users')->where('id', $captainId)->update([
            'email' => self::DEMO_LOGIN_EMAIL,
            'password' => DemoPassword::hash(),
            'name' => 'Capitaine calendrier démo',
            'updated_at' => $now,
        ]);

        $user = User::query()->where('email', self::DEMO_LOGIN_EMAIL)->first();
        if ($user === null) {
            return;
        }

        $user->tokens()->where('name', 'demo-calendar-seed')->delete();
        $user->createToken('demo-calendar-seed', ['*']);
    }
}
