<?php

namespace Tests\Feature\Api\V1\Teams;

use App\Models\User;
use Database\Seeders\SportsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SportsSeeder::class);
    }

    private function sportIdBySlug(string $slug): int
    {
        $id = DB::table('sports')->where('slug', $slug)->value('id');
        $this->assertNotNull($id);

        return (int) $id;
    }

    public function test_authenticated_user_can_create_team_and_list_it_under_created(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;
        $sportId = $this->sportIdBySlug('football');

        $create = $this->postJson('/api/v1/auth/teams', [
            'name' => 'Les Lions Test',
            'sport_id' => $sportId,
            'description' => 'Créneaux mardi.',
            'competition_type' => 'leisure',
            'skill_level' => 'intermediate',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertCreated()
            ->assertJsonPath('team.name', 'Les Lions Test')
            ->assertJsonPath('team.sport.slug', 'football')
            ->assertJsonPath('team.members_count', 1);

        $teamId = (int) $create->json('team.id');

        $this->getJson('/api/v1/auth/teams', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('data.counts.created', 1)
            ->assertJsonPath('data.counts.joined', 0)
            ->assertJsonPath('data.created.0.id', $teamId)
            ->assertJsonPath('data.created.0.members_count', 1);

        $this->assertDatabaseHas('team_members', [
            'team_id' => $teamId,
            'user_id' => $user->id,
            'role' => 'captain',
            'status' => 'active',
        ]);
    }

    public function test_joined_team_appears_only_in_joined_section(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $sportId = $this->sportIdBySlug('running');

        $teamId = DB::table('teams')->insertGetId([
            'creator_id' => $creator->id,
            'sport_id' => $sportId,
            'name' => 'Sunday Runners API',
            'slug' => 'sunday-runners-api',
            'competition_type' => 'leisure',
            'skill_level' => null,
            'description' => null,
            'hq_city' => null,
            'hq_latitude' => null,
            'hq_longitude' => null,
            'cover_image_url' => null,
            'logo_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('team_members')->insert([
            ['team_id' => $teamId, 'user_id' => $creator->id, 'role' => 'captain', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['team_id' => $teamId, 'user_id' => $member->id, 'role' => 'member', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $token = $member->createToken('auth')->plainTextToken;

        $this->getJson('/api/v1/auth/teams', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('data.counts.created', 0)
            ->assertJsonPath('data.counts.joined', 1)
            ->assertJsonPath('data.joined.0.id', $teamId);
    }

    public function test_non_member_cannot_view_team(): void
    {
        $creator = User::factory()->create();
        $stranger = User::factory()->create();
        $sportId = $this->sportIdBySlug('tennis');

        $teamId = DB::table('teams')->insertGetId([
            'creator_id' => $creator->id,
            'sport_id' => $sportId,
            'name' => 'Tennis Club X',
            'slug' => 'tennis-club-x',
            'competition_type' => 'leisure',
            'skill_level' => null,
            'description' => null,
            'hq_city' => null,
            'hq_latitude' => null,
            'hq_longitude' => null,
            'cover_image_url' => null,
            'logo_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('team_members')->insert([
            'team_id' => $teamId,
            'user_id' => $creator->id,
            'role' => 'captain',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = $stranger->createToken('auth')->plainTextToken;

        $this->getJson('/api/v1/auth/teams/'.$teamId, [
            'Authorization' => 'Bearer '.$token,
        ])->assertForbidden();
    }

    public function test_creator_can_update_and_delete_team(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;
        $sportId = $this->sportIdBySlug('basketball');

        $teamId = $this->postJson('/api/v1/auth/teams', [
            'name' => 'Basket City',
            'sport_id' => $sportId,
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertCreated()
            ->json('team.id');

        $this->patchJson('/api/v1/auth/teams/'.$teamId, [
            'name' => 'Basket City Elite',
            'description' => 'Nouvelle description.',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('team.name', 'Basket City Elite')
            ->assertJsonPath('team.slug', 'basket-city-elite');

        $this->deleteJson('/api/v1/auth/teams/'.$teamId, [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();

        $this->assertDatabaseMissing('teams', ['id' => $teamId]);
    }

    public function test_non_creator_member_cannot_delete_team(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $sportId = $this->sportIdBySlug('yoga');

        $teamId = DB::table('teams')->insertGetId([
            'creator_id' => $creator->id,
            'sport_id' => $sportId,
            'name' => 'Yoga Club',
            'slug' => 'yoga-club-test',
            'competition_type' => 'leisure',
            'skill_level' => null,
            'description' => null,
            'hq_city' => null,
            'hq_latitude' => null,
            'hq_longitude' => null,
            'cover_image_url' => null,
            'logo_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('team_members')->insert([
            ['team_id' => $teamId, 'user_id' => $creator->id, 'role' => 'captain', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['team_id' => $teamId, 'user_id' => $member->id, 'role' => 'member', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $token = $member->createToken('auth')->plainTextToken;

        $this->deleteJson('/api/v1/auth/teams/'.$teamId, [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertForbidden();
    }
}
