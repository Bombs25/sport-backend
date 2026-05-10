<?php

namespace Database\Seeders;

use App\Models\User;
use App\Notifications\CommentLikeNotification;
use App\Notifications\Comments;
use App\Notifications\MatchResultLikeNotification;
use App\Notifications\ResponseCommentLikeNotification;
use App\Notifications\SportTopRankChangeNotification;
use App\Notifications\TeamAverageProgressNotification;
use App\Notifications\TeamTopRankChangeNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JsonException;

/**
 * Insère des lignes {@see notifications} cohérentes avec les notifications « database » de l’app.
 *
 * Cible le compte {@see self::FEED_DEMO_EMAIL} jusqu’à au moins {@see self::TARGET_FEED_NOTIFICATIONS} lignes.
 *
 * Idempotent : ne complète que le manquant (comptage sur {@code notifiable_id} du compte feed).
 */
class DemoNotificationsSeeder extends Seeder
{
    private const FEED_DEMO_EMAIL = 'feed.demo@osport.local';

    private const DEMO_HOME_SLUG = 'demo-match-social-home';

    /** Objectif minimum de notifications pour le compte feed démo. */
    private const TARGET_FEED_NOTIFICATIONS = 1000;

    private const INSERT_CHUNK = 500;

    private const PROGRESS_EVERY = 250;

    public function run(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('notifications')) {
            return;
        }

        $feedUserId = $this->resolveUserIdByEmail(self::FEED_DEMO_EMAIL);
        if ($feedUserId === null) {
            if ($this->command !== null) {
                $this->command->warn('DemoNotificationsSeeder : utilisateur '.self::FEED_DEMO_EMAIL.' introuvable, ignoré.');
            }

            return;
        }

        $existing = (int) DB::table('notifications')->where('notifiable_id', (string) $feedUserId)->count();
        $need = max(0, self::TARGET_FEED_NOTIFICATIONS - $existing);

        if ($need === 0) {
            if ($this->command !== null) {
                $this->command->info('DemoNotificationsSeeder : déjà '.$existing.' notification(s) (≥ '.self::TARGET_FEED_NOTIFICATIONS.'), rien à ajouter.');
            }

            return;
        }

        $matchIds = DB::table('match_results')
            ->where('status', 'validated')
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->values()
            ->all();

        if ($matchIds === []) {
            if ($this->command !== null) {
                $this->command->warn('DemoNotificationsSeeder : aucun match_result validé, ignoré.');
            }

            return;
        }

        $actorIds = DB::table('users')
            ->orderBy('id')
            ->limit(500)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->values()
            ->all();

        if ($actorIds === []) {
            $actorIds = [$feedUserId];
        }

        $namesByUserId = $this->resolveDisplayNamesByUserId($actorIds);

        $commentSamples = DB::table('comments')
            ->where('publication_type', 'automatic')
            ->orderBy('id')
            ->limit(5000)
            ->get(['id', 'publication_id']);

        $responseByCommentId = [];
        if ($commentSamples->isNotEmpty()) {
            $commentIds = $commentSamples->pluck('id')->map(static fn ($id): int => (int) $id)->all();
            $responses = DB::table('response_commentaires')
                ->whereIn('comment_id', $commentIds)
                ->orderBy('id')
                ->get(['id', 'comment_id']);
            foreach ($responses as $r) {
                $cid = (int) $r->comment_id;
                if (! isset($responseByCommentId[$cid])) {
                    $responseByCommentId[$cid] = (int) $r->id;
                }
            }
        }

        $demoTeam = DB::table('teams')->where('slug', self::DEMO_HOME_SLUG)->first(['id', 'name']);
        if ($demoTeam === null) {
            $demoTeam = DB::table('teams')->orderBy('id')->first(['id', 'name']);
        }
        $teamId = $demoTeam !== null ? (int) $demoTeam->id : 1;
        $teamName = $demoTeam !== null ? (string) $demoTeam->name : 'Équipe démo';
        $sportId = DB::table('teams')->where('id', $teamId)->value('sport_id');
        $sportId = $sportId !== null ? (int) $sportId : 1;

        $matchCount = count($matchIds);
        $actorCount = count($actorIds);
        $commentCount = $commentSamples->count();

        $baseTime = now()->utc()->subDays(120);
        $buffer = [];
        $inserted = 0;

        for ($i = 0; $i < $need; $i++) {
            $pubId = $matchIds[$i % $matchCount];
            $actorId = $actorIds[$i % $actorCount];
            $actorName = $namesByUserId[$actorId] ?? 'Joueur '.$actorId;
            $createdAt = $baseTime->copy()->addSeconds($i * 37);

            $readAt = match ($i % 5) {
                0, 2 => $createdAt->copy()->addMinutes(30),
                default => null,
            };

            $bucket = $i % 20;
            if ($bucket < 9) {
                $n = new MatchResultLikeNotification(
                    $pubId,
                    $actorId,
                    $i % 2 === 0 ? 'automatic' : 'regular',
                    $actorName,
                );
                $data = $n->toArray((object) []);
                $type = MatchResultLikeNotification::class;
            } elseif ($bucket < 15) {
                $n = new Comments(
                    $pubId,
                    $actorId,
                    'automatic',
                    'Démo notification #'.($existing + $i + 1),
                    $actorName,
                    $i % 2 === 1,
                );
                $data = $n->toArray((object) []);
                $type = Comments::class;
            } elseif ($bucket < 17 && $commentCount > 0) {
                $c = $commentSamples[$i % $commentCount];
                $cid = (int) $c->id;
                $cpub = (int) $c->publication_id;
                $n = new CommentLikeNotification($cpub, $cid, $actorId, 'automatic', $actorName);
                $data = $n->toArray((object) []);
                $type = CommentLikeNotification::class;
            } elseif ($bucket < 18 && $commentCount > 0) {
                $c = $commentSamples[$i % $commentCount];
                $cid = (int) $c->id;
                $cpub = (int) $c->publication_id;
                $rid = $responseByCommentId[$cid] ?? 0;
                if ($rid > 0) {
                    $n = new ResponseCommentLikeNotification(
                        $cpub,
                        $cid,
                        $rid,
                        $actorId,
                        'automatic',
                        $actorName,
                    );
                    $data = $n->toArray((object) []);
                    $type = ResponseCommentLikeNotification::class;
                } else {
                    $n = new MatchResultLikeNotification($pubId, $actorId, 'automatic', $actorName);
                    $data = $n->toArray((object) []);
                    $type = MatchResultLikeNotification::class;
                }
            } elseif ($bucket < 19) {
                $changeType = $i % 2 === 0 ? 'entered_top_3' : 'left_top_3';
                $n = new TeamTopRankChangeNotification($teamId, $teamName, 4, 2, $changeType);
                $data = $n->toArray((object) []);
                $type = TeamTopRankChangeNotification::class;
            } else {
                $sub = $i % 3;
                if ($sub === 0) {
                    $n = new SportTopRankChangeNotification($sportId, $teamId, $teamName, 5, 3, 'entered_top_3');
                    $data = $n->toArray((object) []);
                    $type = SportTopRankChangeNotification::class;
                } elseif ($sub === 1) {
                    $n = new TeamAverageProgressNotification($teamId, $teamName, 10, 2.1, 3.4, 3 + ($i % 5));
                    $data = $n->toArray((object) []);
                    $type = TeamAverageProgressNotification::class;
                } else {
                    $n = new MatchResultLikeNotification($pubId, $actorId, 'regular', $actorName);
                    $data = $n->toArray((object) []);
                    $type = MatchResultLikeNotification::class;
                }
            }

            $buffer[] = $this->notificationRow($type, $feedUserId, $data, $readAt, $createdAt);
            $inserted++;

            if (count($buffer) >= self::INSERT_CHUNK) {
                DB::table('notifications')->insert($buffer);
                $buffer = [];
                if ($this->command !== null && $inserted % self::PROGRESS_EVERY === 0) {
                    $this->command->info('DemoNotificationsSeeder : '.$inserted.' / '.$need.' insérée(s)…');
                }
            }
        }

        if ($buffer !== []) {
            DB::table('notifications')->insert($buffer);
        }

        if ($this->command !== null) {
            $total = (int) DB::table('notifications')->where('notifiable_id', (string) $feedUserId)->count();
            $this->command->info('DemoNotificationsSeeder : +'.$inserted.' notification(s) ; total feed = '.$total.'.');
        }
    }

    private function resolveUserIdByEmail(string $email): ?int
    {
        $id = DB::table('users')->where('email', $email)->value('id');

        return $id !== null ? (int) $id : null;
    }

    /**
     * @param  array<int, int>  $userIds
     * @return array<int, string>
     */
    private function resolveDisplayNamesByUserId(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $fromProfile = [];
        foreach (
            DB::table('user_profiles')->whereIn('user_id', $userIds)->get(['user_id', 'display_name']) as $row
        ) {
            $fromProfile[(int) $row->user_id] = (string) $row->display_name;
        }

        $missing = array_values(array_diff($userIds, array_keys($fromProfile)));
        $fromUsers = $missing === []
            ? []
            : DB::table('users')->whereIn('id', $missing)->pluck('name', 'id')->map(static fn ($n): string => (string) $n)->all();

        $out = [];
        foreach ($userIds as $uid) {
            $out[$uid] = $fromProfile[$uid] ?? (isset($fromUsers[$uid]) ? (string) $fromUsers[$uid] : 'Joueur '.$uid);
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function notificationRow(
        string $type,
        int $notifiableUserId,
        array $data,
        ?Carbon $readAt,
        Carbon $createdAt,
    ): array {
        try {
            $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $e) {
            throw new \RuntimeException($e->getMessage(), 0, $e);
        }

        return [
            'id' => (string) Str::uuid(),
            'type' => $type,
            'notifiable_type' => User::class,
            'notifiable_id' => (string) $notifiableUserId,
            'data' => $json,
            'read_at' => $readAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }
}
