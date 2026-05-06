<?php

namespace Tests\Feature\Api\V1\Posts;

use App\Models\User;
use Database\Seeders\SportsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FetchPostCommentsTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SportsSeeder::class);
    }

    public function test_lists_comments_filtered_by_publication_and_paginates_and_includes_viewer_like_flag(): void
    {
        /** @var User $viewer */
        $viewer = User::factory()->createOne();

        /** @var User $author */
        $author = User::factory()->createOne();

        DB::table('user_profiles')->insert([
            'user_id' => $author->id,
            'display_name' => 'Auteur Test',
            'handle' => 'auteur_test',
            'avatar_url' => 'https://cdn.test/avatar-auteur.png',
            'bio' => null,
            'is_private' => false,
            'city' => null,
            'location' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $footballId = (int) DB::table('sports')->where('slug', 'football')->value('id');
        $this->assertGreaterThan(0, $footballId);

        $homeTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $author->id,
            'sport_id' => $footballId,
            'name' => 'Home '.uniqid(),
            'slug' => 'home-'.uniqid(),
            'competition_type' => 'leisure',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $awayTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $author->id,
            'sport_id' => $footballId,
            'name' => 'Away '.uniqid(),
            'slug' => 'away-'.uniqid(),
            'competition_type' => 'leisure',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $matchEventId = DB::table('match_events')->insertGetId([
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'scheduled_at' => now()->copy()->addDay(),
            'venue' => null,
            'status' => 'finished',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $publicationId = DB::table('match_results')->insertGetId([
            'match_event_id' => $matchEventId,
            'home_score' => 1,
            'away_score' => 2,
            'total_comments' => 0,
            'total_likes' => 0,
            'status' => 'validated',
            'submitted_by_user_id' => $author->id,
            'submitted_at' => now()->copy()->subDay(),
            'responded_by_user_id' => null,
            'responded_at' => null,
            'validated_at' => now()->copy()->subHours(10),
            'refusal_reason' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $baseCreatedAt = now();
        $likedCommentId = null;

        for ($i = 0; $i < 11; $i++) {
            $createdAt = $baseCreatedAt->copy()->subMinutes(100 - $i);

            $commentId = DB::table('comments')->insertGetId([
                'content' => 'Comment '.$i,
                'publication_id' => $publicationId,
                'publication_type' => 'regular',
                'user_id' => ($i % 2 === 0) ? $author->id : $viewer->id,
                'responses_count' => 0,
                'likes_count' => 0,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            if ($i === 10) {
                $likedCommentId = $commentId;
            }
        }

        // One comment on the other publication_type + one for other publication_id.
        DB::table('comments')->insert([
            'content' => 'Automatic comment',
            'publication_id' => $publicationId,
            'publication_type' => 'automatic',
            'user_id' => $author->id,
            'responses_count' => 0,
            'likes_count' => 0,
            'created_at' => $baseCreatedAt->copy()->subMinutes(1),
            'updated_at' => $baseCreatedAt->copy()->subMinutes(1),
        ]);

        DB::table('comments')->insert([
            'content' => 'Other post comment',
            'publication_id' => (int) $publicationId + 9999,
            'publication_type' => 'regular',
            'user_id' => $author->id,
            'responses_count' => 0,
            'likes_count' => 0,
            'created_at' => $baseCreatedAt->copy()->subMinutes(1),
            'updated_at' => $baseCreatedAt->copy()->subMinutes(1),
        ]);

        $this->assertNotNull($likedCommentId);

        DB::table('comments_likes')->insert([
            'users_id' => $viewer->id,
            'comment_id' => (int) $likedCommentId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/auth/posts/comments?publication_id='.$publicationId.'&publication_type=regular&page=1')
            ->assertOk();

        $response->assertJsonCount(10, 'data');
        $this->assertSame(11, (int) $response->json('meta.pagination.total'));
        $this->assertSame(2, (int) $response->json('meta.pagination.last_page'));
        $this->assertSame('Auteur Test', (string) $response->json('data.0.user_display_name'));
        $this->assertSame('auteur_test', (string) $response->json('data.0.user_handle'));
        $this->assertSame('https://cdn.test/avatar-auteur.png', (string) $response->json('data.0.user_avatar_url'));

        // Le commentaire liké est inséré avec une date la plus récente dans sa série.
        $this->assertTrue((bool) $response->json('data.0.viewer_has_liked'));

        $responsePage2 = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/auth/posts/comments?publication_id='.$publicationId.'&publication_type=regular&page=2')
            ->assertOk();

        $responsePage2->assertJsonCount(1, 'data');
        $this->assertSame(2, (int) $responsePage2->json('meta.pagination.current_page'));
    }

    public function test_viewer_has_liked_is_false_when_no_like_exists(): void
    {
        /** @var User $viewer */
        $viewer = User::factory()->createOne();

        /** @var User $author */
        $author = User::factory()->createOne();

        $footballId = (int) DB::table('sports')->where('slug', 'football')->value('id');

        $homeTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $author->id,
            'sport_id' => $footballId,
            'name' => 'Home '.uniqid(),
            'slug' => 'home-'.uniqid(),
            'competition_type' => 'leisure',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $awayTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $author->id,
            'sport_id' => $footballId,
            'name' => 'Away '.uniqid(),
            'slug' => 'away-'.uniqid(),
            'competition_type' => 'leisure',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $matchEventId = DB::table('match_events')->insertGetId([
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'scheduled_at' => now()->copy()->addDay(),
            'venue' => null,
            'status' => 'finished',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $publicationId = DB::table('match_results')->insertGetId([
            'match_event_id' => $matchEventId,
            'home_score' => 1,
            'away_score' => 2,
            'total_comments' => 0,
            'total_likes' => 0,
            'status' => 'validated',
            'submitted_by_user_id' => $author->id,
            'submitted_at' => now()->copy()->subDay(),
            'responded_by_user_id' => null,
            'responded_at' => null,
            'validated_at' => now()->copy()->subHours(10),
            'refusal_reason' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $commentId = DB::table('comments')->insertGetId([
            'content' => 'Hello',
            'publication_id' => $publicationId,
            'publication_type' => 'regular',
            'user_id' => $author->id,
            'responses_count' => 0,
            'likes_count' => 0,
            'created_at' => now()->copy()->subMinutes(1),
            'updated_at' => now()->copy()->subMinutes(1),
        ]);

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/auth/posts/comments?publication_id='.$publicationId.'&publication_type=regular&page=1')
            ->assertOk();

        $response->assertJsonCount(1, 'data');
        $this->assertSame((int) $commentId, (int) $response->json('data.0.id'));
        $this->assertFalse((bool) $response->json('data.0.viewer_has_liked'));

        $this->assertSame(1, (int) $response->json('meta.pagination.total'));
    }

    public function test_lists_comment_responses_with_viewer_like_flag(): void
    {
        /** @var User $viewer */
        $viewer = User::factory()->createOne();

        /** @var User $author */
        $author = User::factory()->createOne();

        DB::table('user_profiles')->insert([
            'user_id' => $author->id,
            'display_name' => 'Auteur Réponse',
            'handle' => 'auteur_reponse',
            'avatar_url' => 'https://cdn.test/avatar-reponse.png',
            'bio' => null,
            'is_private' => false,
            'city' => null,
            'location' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $footballId = (int) DB::table('sports')->where('slug', 'football')->value('id');
        $homeTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $author->id,
            'sport_id' => $footballId,
            'name' => 'Home '.uniqid(),
            'slug' => 'home-'.uniqid(),
            'competition_type' => 'leisure',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $awayTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $author->id,
            'sport_id' => $footballId,
            'name' => 'Away '.uniqid(),
            'slug' => 'away-'.uniqid(),
            'competition_type' => 'leisure',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $matchEventId = DB::table('match_events')->insertGetId([
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'scheduled_at' => now()->copy()->addDay(),
            'venue' => null,
            'status' => 'finished',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $publicationId = DB::table('match_results')->insertGetId([
            'match_event_id' => $matchEventId,
            'home_score' => 1,
            'away_score' => 2,
            'total_comments' => 0,
            'total_likes' => 0,
            'status' => 'validated',
            'submitted_by_user_id' => $author->id,
            'submitted_at' => now()->copy()->subDay(),
            'responded_by_user_id' => null,
            'responded_at' => null,
            'validated_at' => now()->copy()->subHours(10),
            'refusal_reason' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $commentId = DB::table('comments')->insertGetId([
            'content' => 'Comment with responses',
            'publication_id' => $publicationId,
            'publication_type' => 'regular',
            'user_id' => $author->id,
            'responses_count' => 2,
            'likes_count' => 0,
            'created_at' => now()->copy()->subMinutes(1),
            'updated_at' => now()->copy()->subMinutes(1),
        ]);

        $likedResponseId = DB::table('response_commentaires')->insertGetId([
            'comment_id' => $commentId,
            'is_reponse_to_main_comment' => true,
            'response' => 'Liked response',
            'responded_to_who' => null,
            'users_id' => $author->id,
            'likes_count' => 1,
            'created_at' => now()->copy()->subSeconds(30),
            'updated_at' => now()->copy()->subSeconds(30),
        ]);

        DB::table('response_commentaires')->insertGetId([
            'comment_id' => $commentId,
            'is_reponse_to_main_comment' => false,
            'response' => 'Unliked response',
            'responded_to_who' => 'Auteur Réponse',
            'users_id' => $viewer->id,
            'likes_count' => 0,
            'created_at' => now()->copy()->subSeconds(20),
            'updated_at' => now()->copy()->subSeconds(20),
        ]);

        DB::table('response_comment_like')->insert([
            'user_id' => $viewer->id,
            'responses_comment_id' => $likedResponseId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/auth/posts/comments?publication_id='.$publicationId.'&publication_type=regular&page=1')
            ->assertOk();

        $response->assertJsonCount(1, 'data');
        $this->assertArrayNotHasKey('responses', (array) $response->json('data.0'));
    }

    public function test_lists_comment_responses_in_dedicated_route_with_pagination_and_viewer_like_flag(): void
    {
        /** @var User $viewer */
        $viewer = User::factory()->createOne();
        /** @var User $author */
        $author = User::factory()->createOne();

        DB::table('user_profiles')->insert([
            'user_id' => $author->id,
            'display_name' => 'Auteur Réponses API',
            'handle' => 'auteur_reponses_api',
            'avatar_url' => 'https://cdn.test/avatar-reponses-api.png',
            'bio' => null,
            'is_private' => false,
            'city' => null,
            'location' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $footballId = (int) DB::table('sports')->where('slug', 'football')->value('id');
        $homeTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $author->id,
            'sport_id' => $footballId,
            'name' => 'Home '.uniqid(),
            'slug' => 'home-'.uniqid(),
            'competition_type' => 'leisure',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $awayTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $author->id,
            'sport_id' => $footballId,
            'name' => 'Away '.uniqid(),
            'slug' => 'away-'.uniqid(),
            'competition_type' => 'leisure',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $matchEventId = DB::table('match_events')->insertGetId([
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'scheduled_at' => now()->copy()->addDay(),
            'venue' => null,
            'status' => 'finished',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $publicationId = DB::table('match_results')->insertGetId([
            'match_event_id' => $matchEventId,
            'home_score' => 1,
            'away_score' => 2,
            'total_comments' => 0,
            'total_likes' => 0,
            'status' => 'validated',
            'submitted_by_user_id' => $author->id,
            'submitted_at' => now()->copy()->subDay(),
            'responded_by_user_id' => null,
            'responded_at' => null,
            'validated_at' => now()->copy()->subHours(10),
            'refusal_reason' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $commentId = DB::table('comments')->insertGetId([
            'content' => 'Comment responses paginated',
            'publication_id' => $publicationId,
            'publication_type' => 'regular',
            'user_id' => $author->id,
            'responses_count' => 11,
            'likes_count' => 0,
            'created_at' => now()->copy()->subMinutes(2),
            'updated_at' => now()->copy()->subMinutes(2),
        ]);

        $likedResponseId = null;
        for ($i = 0; $i < 11; $i++) {
            $responseId = DB::table('response_commentaires')->insertGetId([
                'comment_id' => $commentId,
                'is_reponse_to_main_comment' => true,
                'response' => 'Response '.$i,
                'responded_to_who' => null,
                'users_id' => $author->id,
                'likes_count' => 0,
                'created_at' => now()->copy()->subSeconds(120 - $i),
                'updated_at' => now()->copy()->subSeconds(120 - $i),
            ]);

            if ($i === 10) {
                $likedResponseId = $responseId;
            }
        }

        $this->assertNotNull($likedResponseId);
        DB::table('response_comment_like')->insert([
            'user_id' => $viewer->id,
            'responses_comment_id' => (int) $likedResponseId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/auth/posts/'.$publicationId.'/comments/'.$commentId.'/responses?post_type=regular&page=1')
            ->assertOk();

        $response->assertJsonCount(10, 'data');
        $this->assertSame(11, (int) $response->json('meta.pagination.total'));
        $this->assertSame(2, (int) $response->json('meta.pagination.last_page'));
        $this->assertTrue((bool) $response->json('data.0.viewer_has_liked'));

        $responsePage2 = $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/auth/posts/'.$publicationId.'/comments/'.$commentId.'/responses?post_type=regular&page=2')
            ->assertOk();

        $responsePage2->assertJsonCount(1, 'data');
        $this->assertSame(2, (int) $responsePage2->json('meta.pagination.current_page'));
    }
}
