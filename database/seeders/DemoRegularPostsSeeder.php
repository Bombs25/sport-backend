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

        $this->insertMediaForPosts($postIds, $variantBase, $now);
        $this->insertInteractionsForPosts($postIds, $userIds, $variantBase, $now);
    }

    /**
     * @param  array<int, int>  $postIds
     */
    private function insertMediaForPosts(array $postIds, int $variantBase, CarbonInterface $now): void
    {
        $rows = [];

        foreach ($postIds as $offset => $postId) {
            $i = $variantBase + $offset;
            $mediaCount = $i % 4;

            for ($position = 0; $position < $mediaCount; $position++) {
                $rows[] = [
                    'post_id' => $postId,
                    'position' => $position,
                    'path' => 'temps/demo-posts/post-'.$postId.'-'.($position + 1).'.webp',
                    'blurhash' => 'LEHV6nWB2yk8pyo0adR*.7kCMdnj',
                    'alt_text' => 'Image démo du post '.$postId.' #'.($position + 1),
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
