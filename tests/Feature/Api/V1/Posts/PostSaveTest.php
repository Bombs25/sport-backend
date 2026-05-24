<?php

namespace Tests\Feature\Api\V1\Posts;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PostSaveTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function insertRegularPost(int $authorId, array $overrides = []): int
    {
        return DB::table('posts')->insertGetId(array_merge([
            'user_id' => $authorId,
            'body' => 'Post de test',
            'visibility' => 'public',
            'status' => 'published',
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function tokenFor(User $user): string
    {
        return $user->createToken('auth')->plainTextToken;
    }

    public function test_save_regular_post_inserts_row_and_returns_saved_true(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $postId = $this->insertRegularPost($author->id);
        $token = $this->tokenFor($viewer);

        $this->postJson('/api/v1/auth/posts/'.$postId.'/saves', [
            'post_type' => 'regular',
            'action' => 'save',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('data.saved', true)
            ->assertJsonPath('data.changed', true);

        $this->assertDatabaseHas('post_saves', [
            'users_id' => $viewer->id,
            'publication_id' => $postId,
            'publication_type' => 'regular',
        ]);
    }

    public function test_save_is_idempotent_when_already_saved(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $postId = $this->insertRegularPost($author->id);
        $token = $this->tokenFor($viewer);

        DB::table('post_saves')->insert([
            'users_id' => $viewer->id,
            'publication_id' => $postId,
            'publication_type' => 'regular',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/v1/auth/posts/'.$postId.'/saves', [
            'post_type' => 'regular',
            'action' => 'save',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('data.saved', true)
            ->assertJsonPath('data.changed', false);

        $this->assertSame(1, DB::table('post_saves')
            ->where('users_id', $viewer->id)
            ->where('publication_id', $postId)
            ->count());
    }

    public function test_unsave_removes_row(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $postId = $this->insertRegularPost($author->id);
        $token = $this->tokenFor($viewer);

        DB::table('post_saves')->insert([
            'users_id' => $viewer->id,
            'publication_id' => $postId,
            'publication_type' => 'regular',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/v1/auth/posts/'.$postId.'/saves', [
            'post_type' => 'regular',
            'action' => 'unsave',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('data.saved', false)
            ->assertJsonPath('data.changed', true);

        $this->assertDatabaseMissing('post_saves', [
            'users_id' => $viewer->id,
            'publication_id' => $postId,
        ]);
    }

    public function test_viewer_has_saved_is_true_in_post_show_after_save(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $postId = $this->insertRegularPost($author->id);
        $token = $this->tokenFor($viewer);

        DB::table('post_saves')->insert([
            'users_id' => $viewer->id,
            'publication_id' => $postId,
            'publication_type' => 'regular',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/v1/auth/posts/regular/'.$postId, [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('data.viewer_has_saved', true);
    }

    public function test_saved_posts_list_returns_only_viewer_saved_in_desc_order(): void
    {
        $viewer = User::factory()->create();
        $other = User::factory()->create();
        $author = User::factory()->create();
        $token = $this->tokenFor($viewer);

        $postA = $this->insertRegularPost($author->id, ['body' => 'A']);
        $postB = $this->insertRegularPost($author->id, ['body' => 'B']);
        $postC = $this->insertRegularPost($author->id, ['body' => 'C']);

        // Viewer sauve A puis C (B = pas sauvé).
        DB::table('post_saves')->insert([
            'users_id' => $viewer->id, 'publication_id' => $postA, 'publication_type' => 'regular',
            'created_at' => now()->subMinute(), 'updated_at' => now()->subMinute(),
        ]);
        DB::table('post_saves')->insert([
            'users_id' => $viewer->id, 'publication_id' => $postC, 'publication_type' => 'regular',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        // Bruit : un autre user a sauvé B → ne doit PAS apparaître.
        DB::table('post_saves')->insert([
            'users_id' => $other->id, 'publication_id' => $postB, 'publication_type' => 'regular',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/auth/posts/saved', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();

        $response->assertJsonCount(2, 'data');
        // Ordre : DESC sur post_saves.id → C (save plus récent) avant A.
        $response->assertJsonPath('data.0.id', $postC);
        $response->assertJsonPath('data.0.publication_type', 'regular');
        $response->assertJsonPath('data.1.id', $postA);
        $response->assertJsonPath('data.1.publication_type', 'regular');
        $response->assertJsonPath('meta.has_more', false);
    }

    public function test_saved_posts_list_includes_both_regular_and_automatic(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $token = $this->tokenFor($viewer);

        // Fixtures match validé minimum.
        $ctx = $this->createValidatedMatch($author);
        $matchResultId = $ctx['match_result_id'];

        // Post régulier (save plus récent → en première position).
        $regularId = $this->insertRegularPost($author->id, ['body' => 'My text']);

        // Save d'abord le match, puis le post régulier (= save_id desc → regular avant match).
        DB::table('post_saves')->insert([
            'users_id' => $viewer->id, 'publication_id' => $matchResultId,
            'publication_type' => 'automatic',
            'created_at' => now()->subMinute(), 'updated_at' => now()->subMinute(),
        ]);
        DB::table('post_saves')->insert([
            'users_id' => $viewer->id, 'publication_id' => $regularId,
            'publication_type' => 'regular',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/auth/posts/saved', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();

        $response->assertJsonCount(2, 'data');
        // 1er = regular (save le plus récent).
        $response->assertJsonPath('data.0.id', $regularId);
        $response->assertJsonPath('data.0.publication_type', 'regular');
        // 2e = automatic, avec les noms d'équipes mappés.
        $response->assertJsonPath('data.1.id', $matchResultId);
        $response->assertJsonPath('data.1.publication_type', 'automatic');
        $response->assertJsonPath('data.1.home_team_name', 'Test Home');
    }

    /**
     * Crée un match `validated` minimal (FK : teams, match_events, match_results).
     * Sport football seedé requis.
     *
     * @return array{match_result_id: int, match_event_id: int}
     */
    private function createValidatedMatch(User $author): array
    {
        $sport = DB::table('sports')->where('slug', 'football')->first();
        if ($sport === null) {
            $sportId = (int) DB::table('sports')->insertGetId([
                'name' => 'Football', 'slug' => 'football',
                'practice_type' => 'collective',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        } else {
            $sportId = (int) $sport->id;
        }

        $homeTeamId = (int) DB::table('teams')->insertGetId([
            'creator_id' => $author->id, 'sport_id' => $sportId,
            'name' => 'Test Home', 'slug' => 'th-'.uniqid(),
            'competition_type' => 'leisure',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $awayTeamId = (int) DB::table('teams')->insertGetId([
            'creator_id' => $author->id, 'sport_id' => $sportId,
            'name' => 'Test Away', 'slug' => 'ta-'.uniqid(),
            'competition_type' => 'leisure',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $matchEventId = (int) DB::table('match_events')->insertGetId([
            'home_team_id' => $homeTeamId, 'away_team_id' => $awayTeamId,
            'scheduled_at' => now()->subDay()->toDateTimeString(),
            'venue' => null, 'status' => 'finished', 'notes' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $matchResultId = (int) DB::table('match_results')->insertGetId([
            'match_event_id' => $matchEventId,
            'home_score' => 3, 'away_score' => 1,
            'total_comments' => 0, 'total_likes' => 0,
            'status' => 'validated',
            'submitted_by_user_id' => $author->id,
            'submitted_at' => now()->subHour(),
            'responded_by_user_id' => $author->id,
            'responded_at' => now()->subMinutes(30),
            'validated_at' => now()->subMinutes(30),
            'refusal_reason' => null,
            'created_at' => now()->subHour(),
            'updated_at' => now(),
        ]);

        return ['match_result_id' => $matchResultId, 'match_event_id' => $matchEventId];
    }

    public function test_save_requires_authentication(): void
    {
        $author = User::factory()->create();
        $postId = $this->insertRegularPost($author->id);

        $this->postJson('/api/v1/auth/posts/'.$postId.'/saves', [
            'post_type' => 'regular',
            'action' => 'save',
        ])->assertUnauthorized();
    }

    public function test_save_validates_unknown_post(): void
    {
        $viewer = User::factory()->create();
        $token = $this->tokenFor($viewer);

        $this->postJson('/api/v1/auth/posts/999999/saves', [
            'post_type' => 'regular',
            'action' => 'save',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['post_id']);
    }
}
