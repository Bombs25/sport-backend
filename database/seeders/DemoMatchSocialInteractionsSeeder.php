<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Team\MatchResultService;
use App\Support\UserProfileLocation;
use Database\Seeders\Support\DemoPassword;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Jeu de données pour tester le fil « match » : équipes, matchs, résultats (validé / en attente / refusé + litige),
 * commentaires, réponses, likes commentaire, likes réponse, likes publication (post_likes), follows, user_sports.
 *
 * Statuts {@see match_events} / {@see match_results} alignés sur {@see MatchResultService}
 * (refus ou attente ⇒ événement `scheduled` ; validé ⇒ `finished`).
 *
 * Cible au plus {@see MAX_USERS} utilisateurs au total (complète les comptes existants après DemoUsersSeeder).
 * Idempotent : ne rejoue pas si l’équipe {@see MARKER_HOME_SLUG} existe déjà.
 */
class DemoMatchSocialInteractionsSeeder extends Seeder
{
    private const MAX_USERS = 100;

    private const MARKER_HOME_SLUG = 'demo-match-social-home';

    private const MARKER_AWAY_SLUG = 'demo-match-social-away';

    private const DEMO_LOGIN_EMAIL = 'feed.demo@osport.local';

    public function run(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('teams')) {
            return;
        }

        if (DB::table('teams')->where('slug', self::MARKER_HOME_SLUG)->exists()) {
            if ($this->command !== null) {
                $this->command->info('DemoMatchSocialInteractionsSeeder : déjà présent (slug '.self::MARKER_HOME_SLUG.'), ignoré.');
            }

            return;
        }

        $footballId = DB::table('sports')->where('slug', 'football')->value('id');
        if ($footballId === null) {
            $this->call(SportsSeeder::class);
            $footballId = DB::table('sports')->where('slug', 'football')->value('id');
        }
        if ($footballId === null) {
            return;
        }

        $footballId = (int) $footballId;

        $this->topUpUsersAndProfiles($footballId);
        $userIds = DB::table('users')->orderBy('id')->limit(self::MAX_USERS)->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();

        if (count($userIds) < 4) {
            return;
        }

        $now = Carbon::now();
        $handlesByUserId = $this->handlesForUsers($userIds);

        $homeCaptainId = $userIds[0];
        $awayCaptainId = $userIds[count($userIds) >= 51 ? 50 : 1];

        $this->ensureDemoLoginUser($homeCaptainId, $now);

        $homeMemberIds = array_slice($userIds, 0, min(50, (int) ceil(count($userIds) / 2)));
        $awayMemberIds = array_values(array_diff($userIds, $homeMemberIds));
        if ($awayMemberIds === []) {
            return;
        }
        if (! in_array($awayCaptainId, $awayMemberIds, true)) {
            $awayCaptainId = $awayMemberIds[0];
        }

        $homeTeamId = $this->insertTeam(
            $homeCaptainId,
            $footballId,
            'Démo match social — Domicile',
            self::MARKER_HOME_SLUG,
            $now,
        );
        $awayTeamId = $this->insertTeam(
            $awayCaptainId,
            $footballId,
            'Démo match social — Extérieur',
            self::MARKER_AWAY_SLUG,
            $now,
        );

        $this->insertTeamMembers($homeTeamId, $homeMemberIds, $homeCaptainId, $now);
        $this->insertTeamMembers($awayTeamId, $awayMemberIds, $awayCaptainId, $now);

        $this->seedFollows($userIds, $now);

        // --- Match 1 : terminé + résultat validé + 2 évaluations (fil riche)
        $matchEventValidatedId = $this->insertMatchEvent($homeTeamId, $awayTeamId, 'finished', $now->copy()->subDay());
        $matchResultValidatedId = $this->insertMatchResultValidated(
            $matchEventValidatedId,
            $homeCaptainId,
            $awayCaptainId,
            $now,
        );
        $this->insertOpponentEvaluationsPair(
            $matchResultValidatedId,
            $homeTeamId,
            $awayTeamId,
            $homeCaptainId,
            $awayCaptainId,
            $now,
        );

        // --- Match 2 : planifié + résultat en attente + 1ère évaluation seulement
        $matchEventPendingId = $this->insertMatchEvent($homeTeamId, $awayTeamId, 'scheduled', $now->copy()->addDays(3));
        $matchResultPendingId = $this->insertMatchResultPending($matchEventPendingId, $homeCaptainId, $now);
        $this->insertFirstOpponentEvaluationOnly(
            $matchResultPendingId,
            $homeTeamId,
            $awayTeamId,
            $homeCaptainId,
            $now,
        );

        // --- Match 3 : résultat refusé + litige (événement encore `scheduled` tant que le score n’est pas validé)
        $matchEventDisputeId = $this->insertMatchEvent($homeTeamId, $awayTeamId, 'scheduled', $now->copy()->subDays(2));
        $matchResultRefusedId = $this->insertMatchResultRefused($matchEventDisputeId, $homeCaptainId, $awayCaptainId, $now);
        $this->insertMatchResultRefusedEvaluation(
            $matchResultRefusedId,
            $homeTeamId,
            $awayTeamId,
            $homeCaptainId,
            $now,
        );
        $this->insertMatchResultDispute($matchResultRefusedId, $awayCaptainId, $now);

        $this->seedCommentsInteractions(
            $matchResultValidatedId,
            $userIds,
            $handlesByUserId,
            $now,
        );

        $this->seedPostLikes($matchResultValidatedId, $userIds, $now);

        $this->createSanctumTokenForEmail(self::DEMO_LOGIN_EMAIL);

        if ($this->command !== null) {
            $this->command->info('Connexion API démo : '.self::DEMO_LOGIN_EMAIL.' / '.DemoPassword::PLAIN);
            $this->command->info('Résultat validé (publication_id pour posts/*) : '.$matchResultValidatedId);
        }
    }

    private function topUpUsersAndProfiles(int $footballId): void
    {
        $current = (int) DB::table('users')->count();
        $toCreate = max(0, self::MAX_USERS - $current);
        if ($toCreate > 0) {
            User::factory()->count($toCreate)->create();
        }

        $userIds = DB::table('users')->orderBy('id')->limit(self::MAX_USERS)->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $now = now();

        foreach ($userIds as $index => $userId) {
            $handle = 'social_feed_u'.$userId.'_'.Str::lower(Str::random(4));
            $user = DB::table('users')->where('id', $userId)->first();
            $displayName = Str::limit((string) ($user->name ?? 'Joueur '.$userId), 64, '');

            $lat = fake()->randomFloat(7, 43.0, 50.0);
            $lng = fake()->randomFloat(7, -1.5, 7.5);

            DB::table('user_profiles')->updateOrInsert(
                ['user_id' => $userId],
                array_merge([
                    'display_name' => $displayName,
                    'handle' => $handle,
                    'bio' => fake()->optional(0.6)->sentence(),
                    'avatar_url' => null,
                    'is_private' => false,
                    'city' => fake()->randomElement(['Paris', 'Lyon', 'Marseille', 'Lille', 'Bordeaux']),
                    'address_line' => null,
                    'birth_date' => fake()->date('Y-m-d', '-20 years'),
                    'created_at' => $now,
                    'updated_at' => $now,
                ], UserProfileLocation::columnsFromLatLng($lat, $lng)),
            );

            DB::table('user_sports')->updateOrInsert(
                ['user_id' => $userId, 'sport_id' => $footballId],
                [
                    'is_favorite' => $index === 0,
                    'skill_level' => fake()->randomElement(['beginner', 'intermediate', 'expert']),
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );

            $expo = 'ExponentPushToken[demo'.Str::random(16).']';
            DB::table('users')->where('id', $userId)->update([
                'fcm_token' => fake()->boolean(40) ? $expo : null,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * @param  array<int, int>  $userIds
     * @return array<int, string> user_id => handle
     */
    private function handlesForUsers(array $userIds): array
    {
        $rows = DB::table('user_profiles')->whereIn('user_id', $userIds)->get(['user_id', 'handle']);
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->user_id] = (string) $row->handle;
        }

        return $map;
    }

    private function ensureDemoLoginUser(int $homeCaptainId, Carbon $now): void
    {
        DB::table('users')->where('id', $homeCaptainId)->update([
            'email' => self::DEMO_LOGIN_EMAIL,
            'password' => DemoPassword::hash(),
            'name' => 'Capitaine fil démo',
            'updated_at' => $now,
        ]);
    }

    private function insertTeam(int $creatorId, int $sportId, string $name, string $slug, Carbon $now): int
    {
        return (int) DB::table('teams')->insertGetId([
            'creator_id' => $creatorId,
            'sport_id' => $sportId,
            'name' => $name,
            'slug' => $slug,
            'competition_type' => 'leisure',
            'skill_level' => 'intermediate',
            'description' => 'Équipe de démonstration pour le fil match et les interactions.',
            'hq_city' => 'Paris',
            'hq_latitude' => 48.8566,
            'hq_longitude' => 2.3522,
            'cover_image_url' => null,
            'logo_url' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * @param  array<int, int>  $memberUserIds
     */
    private function insertTeamMembers(int $teamId, array $memberUserIds, int $captainId, Carbon $now): void
    {
        $rows = [];
        foreach ($memberUserIds as $userId) {
            $rows[] = [
                'team_id' => $teamId,
                'user_id' => $userId,
                'role' => $userId === $captainId ? 'captain' : 'member',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('team_members')->insert($rows);
    }

    /**
     * @param  array<int, int>  $userIds
     */
    private function seedFollows(array $userIds, Carbon $now): void
    {
        $follows = [];
        foreach ($userIds as $followerId) {
            $targets = collect($userIds)
                ->reject(fn (int $id): bool => $id === $followerId)
                ->shuffle()
                ->take(min(5, count($userIds) - 1))
                ->all();
            foreach ($targets as $followingId) {
                $follows[] = [
                    'follower_id' => $followerId,
                    'following_id' => $followingId,
                    'status' => 'accepted',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        if ($follows !== []) {
            DB::table('follows')->upsert(
                $follows,
                ['follower_id', 'following_id'],
                ['status', 'updated_at'],
            );
        }
    }

    private function insertMatchEvent(int $homeTeamId, int $awayTeamId, string $status, Carbon $scheduledAt): int
    {
        $now = now();

        return (int) DB::table('match_events')->insertGetId([
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'scheduled_at' => $scheduledAt,
            'venue' => 'Terrain démo',
            'status' => $status,
            'notes' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function insertMatchResultValidated(
        int $matchEventId,
        int $submittedByUserId,
        int $respondedByUserId,
        Carbon $now,
    ): int {
        return (int) DB::table('match_results')->insertGetId([
            'match_event_id' => $matchEventId,
            'home_score' => 3,
            'away_score' => 2,
            'total_comments' => 0,
            'total_likes' => 0,
            'status' => 'validated',
            'submitted_by_user_id' => $submittedByUserId,
            'submitted_at' => $now->copy()->subHours(4),
            'responded_by_user_id' => $respondedByUserId,
            'responded_at' => $now->copy()->subHours(2),
            'validated_at' => $now->copy()->subHour(),
            'refusal_reason' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function insertMatchResultPending(int $matchEventId, int $submittedByUserId, Carbon $now): int
    {
        return (int) DB::table('match_results')->insertGetId([
            'match_event_id' => $matchEventId,
            'home_score' => 1,
            'away_score' => 1,
            'total_comments' => 0,
            'total_likes' => 0,
            'status' => 'score_pending_validation',
            'submitted_by_user_id' => $submittedByUserId,
            'submitted_at' => $now,
            'responded_by_user_id' => null,
            'responded_at' => null,
            'validated_at' => null,
            'refusal_reason' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function insertMatchResultRefused(
        int $matchEventId,
        int $submittedByUserId,
        int $respondedByUserId,
        Carbon $now,
    ): int {
        return (int) DB::table('match_results')->insertGetId([
            'match_event_id' => $matchEventId,
            'home_score' => 0,
            'away_score' => 2,
            'total_comments' => 0,
            'total_likes' => 0,
            'status' => 'refused',
            'submitted_by_user_id' => $submittedByUserId,
            'submitted_at' => $now->copy()->subDays(3),
            'responded_by_user_id' => $respondedByUserId,
            'responded_at' => $now->copy()->subDays(2),
            'validated_at' => null,
            'refusal_reason' => 'Score incohérent avec le compte-rendu du match.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function insertOpponentEvaluationsPair(
        int $matchResultId,
        int $homeTeamId,
        int $awayTeamId,
        int $homeCaptainId,
        int $awayCaptainId,
        Carbon $now,
    ): void {
        DB::table('match_opponent_evaluations')->insert([
            [
                'match_result_id' => $matchResultId,
                'evaluator_team_id' => $homeTeamId,
                'evaluator_user_id' => $homeCaptainId,
                'evaluated_team_id' => $awayTeamId,
                'fair_play_rating' => 4,
                'punctuality_rating' => 5,
                'remarks' => 'Bon esprit de jeu.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'match_result_id' => $matchResultId,
                'evaluator_team_id' => $awayTeamId,
                'evaluator_user_id' => $awayCaptainId,
                'evaluated_team_id' => $homeTeamId,
                'fair_play_rating' => 5,
                'punctuality_rating' => 4,
                'remarks' => 'Merci pour le match.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    private function insertFirstOpponentEvaluationOnly(
        int $matchResultId,
        int $homeTeamId,
        int $awayTeamId,
        int $homeCaptainId,
        Carbon $now,
    ): void {
        DB::table('match_opponent_evaluations')->insert([
            'match_result_id' => $matchResultId,
            'evaluator_team_id' => $homeTeamId,
            'evaluator_user_id' => $homeCaptainId,
            'evaluated_team_id' => $awayTeamId,
            'fair_play_rating' => 4,
            'punctuality_rating' => 4,
            'remarks' => 'En attente de validation adverse.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function insertMatchResultRefusedEvaluation(
        int $matchResultId,
        int $homeTeamId,
        int $awayTeamId,
        int $homeCaptainId,
        Carbon $now,
    ): void {
        DB::table('match_opponent_evaluations')->insert([
            'match_result_id' => $matchResultId,
            'evaluator_team_id' => $homeTeamId,
            'evaluator_user_id' => $homeCaptainId,
            'evaluated_team_id' => $awayTeamId,
            'fair_play_rating' => 3,
            'punctuality_rating' => 3,
            'remarks' => 'Évaluation initiale avant refus.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function insertMatchResultDispute(int $matchResultId, int $openedByUserId, Carbon $now): void
    {
        DB::table('match_result_disputes')->insert([
            'match_result_id' => $matchResultId,
            'opened_by_user_id' => $openedByUserId,
            'dispute_reason_score_incorrect' => true,
            'dispute_reason_fair_play' => false,
            'dispute_reason_behavior' => false,
            'details' => 'Nous contestons le score déclaré. Demande de relecture.',
            'evidence_path' => null,
            'evidence_disk' => null,
            'status' => 'pending',
            'moderator_user_id' => null,
            'moderator_notes' => null,
            'resolution_notes' => null,
            'resolved_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * @param  array<int, int>  $userIds
     * @param  array<int, string>  $handlesByUserId
     */
    private function seedCommentsInteractions(
        int $publicationId,
        array $userIds,
        array $handlesByUserId,
        Carbon $now,
    ): void {
        $pubAuto = 'automatic';

        $authorA = $userIds[2];
        $authorB = $userIds[3];
        $authorC = $userIds[4];

        $c1 = (int) DB::table('comments')->insertGetId([
            'content' => 'Super match, félicitations aux deux équipes !',
            'publication_id' => $publicationId,
            'publication_type' => $pubAuto,
            'user_id' => $authorA,
            'responses_count' => 0,
            'likes_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $c2 = (int) DB::table('comments')->insertGetId([
            'content' => 'Le dernier but était magnifique.',
            'publication_id' => $publicationId,
            'publication_type' => $pubAuto,
            'user_id' => $authorB,
            'responses_count' => 0,
            'likes_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $c3 = (int) DB::table('comments')->insertGetId([
            'content' => 'Résumé automatique de la rencontre (démo).',
            'publication_id' => $publicationId,
            'publication_type' => $pubAuto,
            'user_id' => $authorC,
            'responses_count' => 0,
            'likes_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $handleForReply = $handlesByUserId[$authorA] ?? 'joueur_demo';

        $r1 = (int) DB::table('response_commentaires')->insertGetId([
            'comment_id' => $c1,
            'is_reponse_to_main_comment' => true,
            'response' => 'Totalement d’accord !',
            'responded_to_who' => $handleForReply,
            'users_id' => $userIds[5],
            'likes_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $r2 = (int) DB::table('response_commentaires')->insertGetId([
            'comment_id' => $c1,
            'is_reponse_to_main_comment' => true,
            'response' => 'Hâte du prochain face-à-face.',
            'responded_to_who' => null,
            'users_id' => $userIds[6],
            'likes_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('comments')->where('id', $c1)->update([
            'responses_count' => 2,
            'updated_at' => $now,
        ]);

        $likersComment = array_slice($userIds, 7, 5);
        foreach ($likersComment as $uid) {
            DB::table('comments_likes')->insert([
                'users_id' => $uid,
                'comment_id' => $c1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        DB::table('comments')->where('id', $c1)->update([
            'likes_count' => count($likersComment),
            'updated_at' => $now,
        ]);

        DB::table('comments_likes')->insert([
            'users_id' => $userIds[8],
            'comment_id' => $c2,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('comments')->where('id', $c2)->update(['likes_count' => 1, 'updated_at' => $now]);

        DB::table('response_comment_like')->insert([
            'user_id' => $userIds[9],
            'responses_comment_id' => $r1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('response_commentaires')->where('id', $r1)->update(['likes_count' => 1, 'updated_at' => $now]);

        $rootComments = 3;
        $responses = 2;
        DB::table('match_results')->where('id', $publicationId)->update([
            'total_comments' => $rootComments + $responses,
            'updated_at' => $now,
        ]);
    }

    /**
     * @param  array<int, int>  $userIds
     */
    private function seedPostLikes(int $matchResultId, array $userIds, Carbon $now): void
    {
        $pub = 'automatic';
        $likers = array_slice($userIds, 10, min(15, count($userIds) - 10));
        foreach ($likers as $uid) {
            DB::table('post_likes')->insert([
                'users_id' => $uid,
                'publication_id' => $matchResultId,
                'publication_type' => $pub,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        DB::table('match_results')->where('id', $matchResultId)->update([
            'total_likes' => count($likers),
            'updated_at' => $now,
        ]);
    }

    private function createSanctumTokenForEmail(string $email): void
    {
        $user = User::query()->where('email', $email)->first();
        if ($user === null) {
            return;
        }
        $user->tokens()->where('name', 'demo-feed-seed')->delete();
        $user->createToken('demo-feed-seed', ['*']);
    }
}
