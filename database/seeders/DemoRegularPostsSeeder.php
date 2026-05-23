<?php

namespace Database\Seeders;

use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoRegularPostsSeeder extends Seeder
{
    private const DEFAULT_TARGET_MIN = 500;

    private const CHUNK_SIZE = 250;

    private const BODIES = [
        'Séance intense aujourd’hui, les progrès commencent à se voir.',
        'Victoire collective et gros mental jusqu’au dernier point.',
        'Préparation du week-end terminée. Place au terrain.',
        'Très bonne ambiance à l’entraînement, merci à tout le groupe.',
        'Retour en images sur une belle session entre passionnés.',
        'Objectif régularité : une séance de plus dans les jambes.',
    ];

    /**
     * URLs Unsplash stables (CDN `images.unsplash.com`) regroupées par slug sport.
     * On n'utilise PAS `source.unsplash.com` : service déprécié par Unsplash en 2024.
     *
     * @var array<string, list<string>>
     */
    private const UNSPLASH_BY_SPORT = [
        'football' => [
            'https://images.unsplash.com/photo-1431324155629-1a6deb1dec8d?w=1080&q=80&auto=format',
            'https://images.unsplash.com/photo-1551958219-acbc608c6377?w=1080&q=80&auto=format',
            'https://images.unsplash.com/photo-1517466787929-bc90951d0974?w=1080&q=80&auto=format',
            'https://images.unsplash.com/photo-1574629810360-7efbbe195018?w=1080&q=80&auto=format',
            'https://images.unsplash.com/photo-1486286701208-1d58e9338013?w=1080&q=80&auto=format',
            'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?w=1080&q=80&auto=format',
            'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?w=1080&q=80&auto=format',
            'https://images.unsplash.com/photo-1493711662062-fa541adb3fc8?w=1080&q=80&auto=format',
        ],
        'basketball' => [
            'https://images.unsplash.com/photo-1546519638-68e109498ffc?w=1080&q=80&auto=format',
            'https://images.unsplash.com/photo-1518614846040-6c711dbcb8e0?w=1080&q=80&auto=format',
            'https://images.unsplash.com/photo-1574623452334-1e0ac2b3ccb4?w=1080&q=80&auto=format',
            'https://images.unsplash.com/photo-1505666287802-931dc83a0fe4?w=1080&q=80&auto=format',
            'https://images.unsplash.com/photo-1519861531473-9200262188bf?w=1080&q=80&auto=format',
            'https://images.unsplash.com/photo-1504450758481-7338eba7524a?w=1080&q=80&auto=format',
            'https://images.unsplash.com/photo-1577471488278-16eec37ffcc2?w=1080&q=80&auto=format',
            'https://images.unsplash.com/photo-1608245449230-4ac19066d2d0?w=1080&q=80&auto=format',
        ],
        'tennis' => [
            'https://images.unsplash.com/photo-1554068865-24cecd4e34b8?w=1080&q=80&auto=format',
            'https://images.unsplash.com/photo-1622279457486-62dcc4a431d6?w=1080&q=80&auto=format',
            'https://images.unsplash.com/photo-1530915534935-d013d0e75f5b?w=1080&q=80&auto=format',
            'https://images.unsplash.com/photo-1542144582-1ba00456b5e3?w=1080&q=80&auto=format',
            'https://images.unsplash.com/photo-1622279488611-2c44e9d80bff?w=1080&q=80&auto=format',
            'https://images.unsplash.com/photo-1599586120429-48eb5d4c5f1f?w=1080&q=80&auto=format',
            'https://images.unsplash.com/photo-1531315396756-905d68d21b56?w=1080&q=80&auto=format',
            'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=1080&q=80&auto=format',
        ],
        'running' => [
            'https://images.unsplash.com/photo-1552674605-db6ffd4facb5?w=1080&q=80&auto=format',
            'https://images.unsplash.com/photo-1571008887538-b36bb32f4571?w=1080&q=80&auto=format',
            'https://images.unsplash.com/photo-1486218119243-13883505764c?w=1080&q=80&auto=format',
            'https://images.unsplash.com/photo-1452626038306-9aae5e071dd3?w=1080&q=80&auto=format',
            'https://images.unsplash.com/photo-1476480862126-209bfaa8edc8?w=1080&q=80&auto=format',
            'https://images.unsplash.com/photo-1502904550040-7534597429ae?w=1080&q=80&auto=format',
            'https://images.unsplash.com/photo-1466150036782-869a824aeb25?w=1080&q=80&auto=format',
            'https://images.unsplash.com/photo-1490137462308-de9582f6c11a?w=1080&q=80&auto=format',
        ],
    ];

    /**
     * Pool de secours pour les sports hors liste (yoga, padel) ou les users
     * sans `user_sports`.
     *
     * @var list<string>
     */
    private const UNSPLASH_FALLBACK = [
        'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=1080&q=80&auto=format',
        'https://images.unsplash.com/photo-1517649763962-0c623066013b?w=1080&q=80&auto=format',
        'https://images.unsplash.com/photo-1526506118085-60ce8714f8c5?w=1080&q=80&auto=format',
        'https://images.unsplash.com/photo-1530549387789-4c1017266635?w=1080&q=80&auto=format',
        'https://images.unsplash.com/photo-1518611012118-696072aa579a?w=1080&q=80&auto=format',
        'https://images.unsplash.com/photo-1593079831268-3381b0db4a77?w=1080&q=80&auto=format',
        'https://images.unsplash.com/photo-1517438476312-10d79c077509?w=1080&q=80&auto=format',
        'https://images.unsplash.com/photo-1599058917212-d750089bc07e?w=1080&q=80&auto=format',
    ];

    /**
     * Cache `user_id => slug sport` pour éviter une jointure par chunk.
     *
     * @var array<int, string>
     */
    private array $sportByUserId = [];

    public function run(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('posts') || ! DB::getSchemaBuilder()->hasTable('post_media')) {
            return;
        }

        $targetMin = max(0, (int) env('DEMO_REGULAR_POSTS_COUNT', self::DEFAULT_TARGET_MIN));
        $current = (int) DB::table('posts')->count();
        $need = max(0, $targetMin - $current);

        if ($need === 0) {
            $this->command?->info('DemoRegularPostsSeeder : déjà '.$current.' post(s) (≥ '.$targetMin.'), rien à ajouter.');

            return;
        }

        $userIds = DB::table('users')
            ->orderBy('id')
            ->limit(max($targetMin, self::CHUNK_SIZE))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        if ($userIds === []) {
            $this->command?->warn('DemoRegularPostsSeeder : aucun utilisateur disponible, ignoré.');

            return;
        }

        $this->sportByUserId = $this->loadSportByUserId($userIds);

        $now = now();
        $created = 0;
        $variantBase = $current;

        while ($created < $need) {
            $take = min(self::CHUNK_SIZE, $need - $created);

            DB::transaction(function () use ($userIds, $now, $variantBase, $take): void {
                $this->insertPostChunk($userIds, $now, $variantBase, $take);
            });

            $created += $take;
            $variantBase += $take;

            if ($this->command !== null && ($created === $need || $created % 1_000 === 0)) {
                $this->command->info('DemoRegularPostsSeeder : '.$created.' / '.$need.' posts réguliers créés.');
            }
        }

        $this->command?->info('DemoRegularPostsSeeder : total posts='.(int) DB::table('posts')->count().'.');
    }

    /**
     * @param  array<int, int>  $userIds
     */
    private function insertPostChunk(array $userIds, CarbonInterface $now, int $variantBase, int $take): void
    {
        $userCount = count($userIds);
        $rows = [];

        for ($k = 0; $k < $take; $k++) {
            $i = $variantBase + $k;
            $mediaCount = $i % 4;

            $rows[] = [
                'user_id' => $userIds[$i % $userCount],
                'body' => self::BODIES[$i % count(self::BODIES)].' #'.($i + 1),
                'visibility' => 'public',
                'status' => 'published',
                'media_count' => $mediaCount,
                'total_likes' => 0,
                'total_comments' => 0,
                'total_shares' => $i % 5,
                'published_at' => $now->copy()->subMinutes($i * 7),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $idBefore = (int) (DB::table('posts')->max('id') ?? 0);
        DB::table('posts')->insert($rows);
        $postIds = DB::table('posts')
            ->where('id', '>', $idBefore)
            ->orderBy('id')
            ->limit($take)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        if (count($postIds) !== $take) {
            throw new \RuntimeException('DemoRegularPostsSeeder : nombre de posts inséré incohérent.');
        }

        $userByPostId = [];
        foreach ($postIds as $offset => $postId) {
            $userByPostId[$postId] = (int) $rows[$offset]['user_id'];
        }

        $this->insertMediaForPosts($postIds, $userByPostId, $variantBase, $now);
        $this->insertInteractionsForPosts($postIds, $userIds, $variantBase, $now);
    }

    /**
     * Insère les médias d'un chunk de posts en piochant une URL Unsplash dans
     * le pool correspondant au **sport favori de l'auteur** (slug `football`,
     * `basketball`, `tennis`, `running`) ; sinon dans le pool de secours.
     *
     * @param  array<int, int>  $postIds
     * @param  array<int, int>  $userByPostId  Map `post_id => user_id`.
     */
    private function insertMediaForPosts(array $postIds, array $userByPostId, int $variantBase, CarbonInterface $now): void
    {
        $rows = [];

        foreach ($postIds as $offset => $postId) {
            $i = $variantBase + $offset;
            $mediaCount = $i % 4;
            $pool = $this->poolForUser($userByPostId[$postId] ?? 0);
            $poolSize = count($pool);

            for ($position = 0; $position < $mediaCount; $position++) {
                $rows[] = [
                    'post_id' => $postId,
                    'position' => $position,
                    'path' => $pool[($i + $position) % $poolSize],
                    'blurhash' => null,
                    'alt_text' => 'Photo sport démo Unsplash',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($rows !== []) {
            DB::table('post_media')->insert($rows);
        }
    }

    /**
     * Pré-charge `user_id => slug du sport favori` (ou premier rattaché à défaut)
     * en une seule requête Query Builder (§1.7 schéma O'Sport).
     *
     * @param  array<int, int>  $userIds
     * @return array<int, string>
     */
    private function loadSportByUserId(array $userIds): array
    {
        if ($userIds === [] || ! DB::getSchemaBuilder()->hasTable('user_sports')) {
            return [];
        }

        return DB::table('user_sports')
            ->join('sports', 'sports.id', '=', 'user_sports.sport_id')
            ->whereIn('user_sports.user_id', $userIds)
            ->orderByDesc('user_sports.is_favorite')
            ->orderBy('user_sports.id')
            ->get(['user_sports.user_id', 'sports.slug'])
            ->groupBy('user_id')
            ->map(static fn ($rows): string => (string) $rows->first()->slug)
            ->all();
    }

    /**
     * @return list<string>
     */
    private function poolForUser(int $userId): array
    {
        $slug = $this->sportByUserId[$userId] ?? null;

        return self::UNSPLASH_BY_SPORT[$slug] ?? self::UNSPLASH_FALLBACK;
    }

    /**
     * @param  array<int, int>  $postIds
     * @param  array<int, int>  $userIds
     */
    private function insertInteractionsForPosts(array $postIds, array $userIds, int $variantBase, CarbonInterface $now): void
    {
        if (
            ! DB::getSchemaBuilder()->hasTable('comments')
            || ! DB::getSchemaBuilder()->hasTable('response_commentaires')
            || ! DB::getSchemaBuilder()->hasTable('post_likes')
        ) {
            return;
        }

        $userCount = count($userIds);

        foreach ($postIds as $offset => $postId) {
            $i = $variantBase + $offset;
            $commentIds = $this->insertCommentsForPost($postId, $userIds, $userCount, $i, $now);
            $responseIds = $this->insertResponsesForPost($commentIds, $userIds, $userCount, $i, $now);
            $likesCount = $this->insertLikesForPost($postId, $userIds, $userCount, $i, $now);
            $this->insertCommentLikes($commentIds, $userIds, $userCount, $i, $now);
            $this->insertResponseLikes($responseIds, $userIds, $userCount, $i, $now);

            DB::table('posts')
                ->where('id', $postId)
                ->update([
                    'total_comments' => count($commentIds) + count($responseIds),
                    'total_likes' => $likesCount,
                    'updated_at' => $now,
                ]);
        }
    }

    /**
     * @param  array<int, int>  $userIds
     * @return array<int, int>
     */
    private function insertCommentsForPost(int $postId, array $userIds, int $userCount, int $seed, CarbonInterface $now): array
    {
        $commentCount = $seed % 3;
        $commentIds = [];

        for ($j = 0; $j < $commentCount; $j++) {
            $commentIds[] = (int) DB::table('comments')->insertGetId([
                'content' => 'Commentaire regular démo #'.($seed + 1).'.'.($j + 1),
                'publication_id' => $postId,
                'publication_type' => 'regular',
                'user_id' => $userIds[($seed + $j + 1) % $userCount],
                'responses_count' => 0,
                'likes_count' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return $commentIds;
    }

    /**
     * @param  array<int, int>  $commentIds
     * @param  array<int, int>  $userIds
     */
    private function insertResponsesForPost(array $commentIds, array $userIds, int $userCount, int $seed, CarbonInterface $now): array
    {
        if ($commentIds === []) {
            return [];
        }

        $responseIds = [];
        foreach ($commentIds as $index => $commentId) {
            if (($seed + $index) % 2 !== 0) {
                continue;
            }

            $responseIds[] = (int) DB::table('response_commentaires')->insertGetId([
                'comment_id' => $commentId,
                'is_reponse_to_main_comment' => true,
                'response' => 'Réponse regular démo #'.($seed + 1).'.'.($index + 1),
                'responded_to_who' => null,
                'users_id' => $userIds[($seed + $index + 3) % $userCount],
                'likes_count' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('comments')
                ->where('id', $commentId)
                ->increment('responses_count');
        }

        return $responseIds;
    }

    /**
     * @param  array<int, int>  $userIds
     */
    private function insertLikesForPost(int $postId, array $userIds, int $userCount, int $seed, CarbonInterface $now): int
    {
        $likeCount = min($userCount, $seed % 8);
        $rows = [];

        for ($j = 0; $j < $likeCount; $j++) {
            $rows[] = [
                'users_id' => $userIds[($seed + $j + 5) % $userCount],
                'publication_id' => $postId,
                'publication_type' => 'regular',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            DB::table('post_likes')->insert($rows);
        }

        return count($rows);
    }

    /**
     * @param  array<int, int>  $commentIds
     * @param  array<int, int>  $userIds
     */
    private function insertCommentLikes(array $commentIds, array $userIds, int $userCount, int $seed, CarbonInterface $now): void
    {
        if ($commentIds === [] || ! DB::getSchemaBuilder()->hasTable('comments_likes')) {
            return;
        }

        foreach ($commentIds as $index => $commentId) {
            $likeCount = min($userCount, ($seed + $index) % 4);
            $rows = [];

            for ($j = 0; $j < $likeCount; $j++) {
                $rows[] = [
                    'users_id' => $userIds[($seed + $index + $j + 7) % $userCount],
                    'comment_id' => $commentId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($rows === []) {
                continue;
            }

            DB::table('comments_likes')->insert($rows);
            DB::table('comments')
                ->where('id', $commentId)
                ->update([
                    'likes_count' => count($rows),
                    'updated_at' => $now,
                ]);
        }
    }

    /**
     * @param  array<int, int>  $responseIds
     * @param  array<int, int>  $userIds
     */
    private function insertResponseLikes(array $responseIds, array $userIds, int $userCount, int $seed, CarbonInterface $now): void
    {
        if ($responseIds === [] || ! DB::getSchemaBuilder()->hasTable('response_comment_like')) {
            return;
        }

        foreach ($responseIds as $index => $responseId) {
            if (($seed + $index) % 3 !== 0) {
                continue;
            }

            DB::table('response_comment_like')->insert([
                'user_id' => $userIds[($seed + $index + 11) % $userCount],
                'responses_comment_id' => $responseId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('response_commentaires')
                ->where('id', $responseId)
                ->update([
                    'likes_count' => 1,
                    'updated_at' => $now,
                ]);
        }
    }
}
