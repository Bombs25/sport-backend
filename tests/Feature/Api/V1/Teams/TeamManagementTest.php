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

    public function test_user_can_request_integration_and_captain_can_accept_it(): void
    {
        $creator = User::factory()->create();
        $candidate = User::factory()->create();
        $sportId = $this->sportIdBySlug('football');

        $teamId = DB::table('teams')->insertGetId([
            'creator_id' => $creator->id,
            'sport_id' => $sportId,
            'name' => 'FC Integration',
            'slug' => 'fc-integration',
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

        $creatorToken = $creator->createToken('auth')->plainTextToken;

        DB::table('team_members')->insert([
            'team_id' => $teamId,
            'user_id' => $candidate->id,
            'role' => 'member',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('team_members', [
            'team_id' => $teamId,
            'user_id' => $candidate->id,
            'status' => 'pending',
            'role' => 'member',
        ]);

        $this->withToken($creatorToken)
            ->patchJson('/api/v1/auth/teams/'.$teamId.'/integrations/'.$candidate->id, [
                'decision' => 'accept',
            ])
            ->assertOk();

        $this->assertDatabaseHas('team_members', [
            'team_id' => $teamId,
            'user_id' => $candidate->id,
            'status' => 'active',
            'role' => 'member',
        ]);
    }

    public function test_only_creator_or_captain_can_accept_or_refuse_integration(): void
    {
        $creator = User::factory()->create();
        $candidate = User::factory()->create();
        $outsider = User::factory()->create();
        $sportId = $this->sportIdBySlug('basketball');

        $teamId = DB::table('teams')->insertGetId([
            'creator_id' => $creator->id,
            'sport_id' => $sportId,
            'name' => 'Hoops Control',
            'slug' => 'hoops-control',
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
            ['team_id' => $teamId, 'user_id' => $outsider->id, 'role' => 'member', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('team_members')->insert([
            'team_id' => $teamId,
            'user_id' => $candidate->id,
            'role' => 'member',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $outsiderToken = $outsider->createToken('auth')->plainTextToken;

        $this->withToken($outsiderToken)
            ->patchJson('/api/v1/auth/teams/'.$teamId.'/integrations/'.$candidate->id, [
                'decision' => 'refuse',
            ])
            ->assertForbidden();
    }

    public function test_creator_can_refuse_pending_integration(): void
    {
        $creator = User::factory()->create();
        $candidate = User::factory()->create();
        $sportId = $this->sportIdBySlug('basketball');

        $teamId = DB::table('teams')->insertGetId([
            'creator_id' => $creator->id,
            'sport_id' => $sportId,
            'name' => 'Hoops Refuse',
            'slug' => 'hoops-refuse',
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
        ]);
        DB::table('team_members')->insert([
            'team_id' => $teamId,
            'user_id' => $candidate->id,
            'role' => 'member',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $creatorToken = $creator->createToken('auth')->plainTextToken;

        $this->withToken($creatorToken)
            ->patchJson('/api/v1/auth/teams/'.$teamId.'/integrations/'.$candidate->id, [
                'decision' => 'refuse',
            ])
            ->assertOk();

        $this->assertDatabaseHas('team_members', [
            'team_id' => $teamId,
            'user_id' => $candidate->id,
            'status' => 'rejected',
        ]);
    }

    public function test_integration_is_rejected_for_non_collective_sport_team(): void
    {
        $creator = User::factory()->create();
        $candidate = User::factory()->create();
        $sportId = $this->sportIdBySlug('running');

        $teamId = DB::table('teams')->insertGetId([
            'creator_id' => $creator->id,
            'sport_id' => $sportId,
            'name' => 'Run Solo Group',
            'slug' => 'run-solo-group',
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

        $candidateToken = $candidate->createToken('auth')->plainTextToken;

        $this->postJson('/api/v1/auth/teams/'.$teamId.'/integrations', [], [
            'Authorization' => 'Bearer '.$candidateToken,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['team_id']);
    }

    public function test_user_cannot_be_active_in_two_teams_of_same_sport(): void
    {
        $creatorOne = User::factory()->create();
        $creatorTwo = User::factory()->create();
        $candidate = User::factory()->create();
        $sportId = $this->sportIdBySlug('padel');

        $teamOneId = DB::table('teams')->insertGetId([
            'creator_id' => $creatorOne->id,
            'sport_id' => $sportId,
            'name' => 'Padel Prime',
            'slug' => 'padel-prime',
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

        $teamTwoId = DB::table('teams')->insertGetId([
            'creator_id' => $creatorTwo->id,
            'sport_id' => $sportId,
            'name' => 'Padel Seconds',
            'slug' => 'padel-seconds',
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
            ['team_id' => $teamOneId, 'user_id' => $creatorOne->id, 'role' => 'captain', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['team_id' => $teamOneId, 'user_id' => $candidate->id, 'role' => 'member', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['team_id' => $teamTwoId, 'user_id' => $creatorTwo->id, 'role' => 'captain', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $candidateToken = $candidate->createToken('auth')->plainTextToken;

        $this->postJson('/api/v1/auth/teams/'.$teamTwoId.'/integrations', [], [
            'Authorization' => 'Bearer '.$candidateToken,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['sport_id']);
    }

    public function test_member_can_leave_team(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $sportId = $this->sportIdBySlug('football');

        $teamId = DB::table('teams')->insertGetId([
            'creator_id' => $creator->id,
            'sport_id' => $sportId,
            'name' => 'Team Leave Test',
            'slug' => 'team-leave-test',
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

        $memberToken = $member->createToken('auth')->plainTextToken;

        $this->withToken($memberToken)
            ->deleteJson('/api/v1/auth/teams/'.$teamId.'/members/'.$member->id)
            ->assertOk();

        $this->assertDatabaseHas('team_members', [
            'team_id' => $teamId,
            'user_id' => $member->id,
            'status' => 'left',
        ]);
    }

    public function test_creator_or_captain_can_remove_active_member(): void
    {
        $creator = User::factory()->create();
        $captain = User::factory()->create();
        $member = User::factory()->create();
        $sportId = $this->sportIdBySlug('basketball');

        $teamId = DB::table('teams')->insertGetId([
            'creator_id' => $creator->id,
            'sport_id' => $sportId,
            'name' => 'Team Remove Member',
            'slug' => 'team-remove-member',
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
            ['team_id' => $teamId, 'user_id' => $captain->id, 'role' => 'captain', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['team_id' => $teamId, 'user_id' => $member->id, 'role' => 'member', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $captainToken = $captain->createToken('auth')->plainTextToken;

        $this->withToken($captainToken)
            ->deleteJson('/api/v1/auth/teams/'.$teamId.'/members/'.$member->id)
            ->assertOk();

        $this->assertDatabaseHas('team_members', [
            'team_id' => $teamId,
            'user_id' => $member->id,
            'status' => 'left',
        ]);
    }

    public function test_outsider_cannot_remove_team_member(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $outsider = User::factory()->create();
        $sportId = $this->sportIdBySlug('tennis');

        $teamId = DB::table('teams')->insertGetId([
            'creator_id' => $creator->id,
            'sport_id' => $sportId,
            'name' => 'Team Unauthorized Remove',
            'slug' => 'team-unauthorized-remove',
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

        $outsiderToken = $outsider->createToken('auth')->plainTextToken;

        $this->withToken($outsiderToken)
            ->deleteJson('/api/v1/auth/teams/'.$teamId.'/members/'.$member->id)
            ->assertForbidden();
    }

    public function test_membership_status_returns_active_member_state(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $sportId = $this->sportIdBySlug('football');

        $teamId = DB::table('teams')->insertGetId([
            'creator_id' => $creator->id,
            'sport_id' => $sportId,
            'name' => 'Team Membership Active',
            'slug' => 'team-membership-active',
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

        $memberToken = $member->createToken('auth')->plainTextToken;

        $this->withToken($memberToken)
            ->getJson('/api/v1/auth/teams/'.$teamId.'/membership')
            ->assertOk()
            ->assertJsonPath('data.team_id', $teamId)
            ->assertJsonPath('data.is_member', true)
            ->assertJsonPath('data.integration_requested', false)
            ->assertJsonPath('data.membership_status', 'active')
            ->assertJsonPath('data.role', 'member');
    }

    public function test_membership_status_returns_pending_integration_state(): void
    {
        $creator = User::factory()->create();
        $candidate = User::factory()->create();
        $sportId = $this->sportIdBySlug('basketball');

        $teamId = DB::table('teams')->insertGetId([
            'creator_id' => $creator->id,
            'sport_id' => $sportId,
            'name' => 'Team Membership Pending',
            'slug' => 'team-membership-pending',
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
            ['team_id' => $teamId, 'user_id' => $candidate->id, 'role' => 'member', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $candidateToken = $candidate->createToken('auth')->plainTextToken;

        $this->withToken($candidateToken)
            ->getJson('/api/v1/auth/teams/'.$teamId.'/membership')
            ->assertOk()
            ->assertJsonPath('data.is_member', false)
            ->assertJsonPath('data.integration_requested', true)
            ->assertJsonPath('data.membership_status', 'pending')
            ->assertJsonPath('data.role', 'member');
    }

    public function test_creator_can_list_pending_integrations_with_ten_items_per_page(): void
    {
        $creator = User::factory()->create();
        $sportId = $this->sportIdBySlug('football');

        $teamId = DB::table('teams')->insertGetId([
            'creator_id' => $creator->id,
            'sport_id' => $sportId,
            'name' => 'Team Pending Pagination',
            'slug' => 'team-pending-pagination',
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

        foreach (range(1, 12) as $i) {
            $candidate = User::factory()->create();

            DB::table('team_members')->insert([
                'team_id' => $teamId,
                'user_id' => $candidate->id,
                'role' => 'member',
                'status' => 'pending',
                'created_at' => now()->subMinutes(12 - $i),
                'updated_at' => now()->subMinutes(12 - $i),
            ]);
        }

        $creatorToken = $creator->createToken('auth')->plainTextToken;

        $this->withToken($creatorToken)
            ->getJson('/api/v1/auth/teams/'.$teamId.'/integrations/pending')
            ->assertOk()
            ->assertJsonCount(10, 'data.items')
            ->assertJsonPath('data.pagination.current_page', 1)
            ->assertJsonPath('data.pagination.per_page', 10)
            ->assertJsonPath('data.pagination.total', 12)
            ->assertJsonPath('data.pagination.last_page', 2);

        $this->withToken($creatorToken)
            ->getJson('/api/v1/auth/teams/'.$teamId.'/integrations/pending?page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data.items')
            ->assertJsonPath('data.pagination.current_page', 2);
    }

    public function test_non_captain_member_cannot_list_pending_integrations(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $sportId = $this->sportIdBySlug('basketball');

        $teamId = DB::table('teams')->insertGetId([
            'creator_id' => $creator->id,
            'sport_id' => $sportId,
            'name' => 'Team Pending Access',
            'slug' => 'team-pending-access',
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

        $memberToken = $member->createToken('auth')->plainTextToken;

        $this->withToken($memberToken)
            ->getJson('/api/v1/auth/teams/'.$teamId.'/integrations/pending')
            ->assertForbidden();
    }

    public function test_authenticated_user_can_get_team_profile_payload_with_members(): void
    {
        $viewer = User::factory()->create();
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $sportId = $this->sportIdBySlug('football');

        $teamId = DB::table('teams')->insertGetId([
            'creator_id' => $creator->id,
            'sport_id' => $sportId,
            'name' => 'Team Profile View',
            'slug' => 'team-profile-view',
            'competition_type' => 'leisure',
            'skill_level' => null,
            'description' => null,
            'hq_city' => 'Paris',
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

        DB::table('user_profiles')->insert([
            'user_id' => $member->id,
            'display_name' => 'Member Profile',
            'handle' => 'member-profile-'.$member->id,
            'bio' => null,
            'avatar_url' => 'https://cdn.test/avatar-member.jpg',
            'is_private' => false,
            'latitude' => null,
            'longitude' => null,
            'city' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $viewerToken = $viewer->createToken('auth')->plainTextToken;

        $response = $this->withToken($viewerToken)
            ->getJson('/api/v1/auth/teams/'.$teamId.'/profile')
            ->assertOk()
            ->assertJsonPath('data.id', $teamId)
            ->assertJsonPath('data.name', 'Team Profile View')
            ->assertJsonPath('data.hq_city', 'Paris')
            ->assertJsonPath('data.sport.id', $sportId)
            ->assertJsonPath('data.sport.slug', 'football')
            ->assertJsonPath('data.sport.practice_type', 'collective')
            ->assertJsonPath('data.members_count', 2)
            ->assertJsonPath('data.members.pagination.current_page', 1)
            ->assertJsonPath('data.members.pagination.per_page', 10)
            ->assertJsonPath('data.members.pagination.total', 2)
            ->assertJsonPath('data.members.pagination.last_page', 1);

        $this->assertCount(2, $response->json('data.members.items'));
    }
}
