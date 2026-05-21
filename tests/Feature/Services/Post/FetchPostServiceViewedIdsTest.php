<?php

namespace Tests\Feature\Services\Post;

use App\Models\User;
use App\Services\Post\FetchPostService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FetchPostServiceViewedIdsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_resolve_viewed_regular_post_ids_unions_client_list_with_persisted_history(): void
    {
        $user = User::factory()->createOne();

        // Historique persisté en base : posts 1 et 2 déjà vus.
        DB::table('user_post_views')->insert([
            $this->viewRow($user->id, 1, FetchPostService::PUBLICATION_TYPE_REGULAR),
            $this->viewRow($user->id, 2, FetchPostService::PUBLICATION_TYPE_REGULAR),
        ]);

        // Le client n'envoie que sa session courante (2 et 3) ; le post 1 n'est connu que de la base.
        $resolved = app(FetchPostService::class)->resolveViewedRegularPostIds($user->id, [2, 3]);

        sort($resolved);
        $this->assertSame([1, 2, 3], $resolved);
    }

    public function test_resolve_viewed_match_result_ids_unions_client_list_with_persisted_history(): void
    {
        $user = User::factory()->createOne();

        DB::table('user_post_views')->insert([
            $this->viewRow($user->id, 10, FetchPostService::PUBLICATION_TYPE_MATCH_RESULT),
            $this->viewRow($user->id, 11, FetchPostService::PUBLICATION_TYPE_MATCH_RESULT),
        ]);

        $resolved = app(FetchPostService::class)->resolveViewedMatchResultIds($user->id, [11, 12]);

        sort($resolved);
        $this->assertSame([10, 11, 12], $resolved);
    }

    /**
     * @return array<string, mixed>
     */
    private function viewRow(int $userId, int $publicationId, string $publicationType): array
    {
        return [
            'user_id' => $userId,
            'publication_id' => $publicationId,
            'publication_type' => $publicationType,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
