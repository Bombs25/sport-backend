<?php

namespace Tests\Feature\Api\V1\Teams;

use App\Models\User;
use App\Support\UserProfileLocation;
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

        DB::table('user_profiles')->insert(array_merge([
            'user_id' => $member->id,
            'display_name' => 'Member Profile',
            'handle' => 'member-profile-'.$member->id,
            'bio' => null,
            'avatar_url' => 'https://cdn.test/avatar-member.jpg',
            'is_private' => false,
            'city' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], UserProfileLocation::columnsFromLatLng(null, null)));

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

    public function test_captain_can_request_match_between_two_teams_of_same_sport(): void
    {
        $captain = User::factory()->create();
        $awayCreator = User::factory()->create();
        $sportId = $this->sportIdBySlug('football');

        $homeTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $captain->id,
            'sport_id' => $sportId,
            'name' => 'Match Home Team',
            'slug' => 'match-home-team',
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

        $awayTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $awayCreator->id,
            'sport_id' => $sportId,
            'name' => 'Match Away Team',
            'slug' => 'match-away-team',
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
            ['team_id' => $homeTeamId, 'user_id' => $captain->id, 'role' => 'captain', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['team_id' => $awayTeamId, 'user_id' => $awayCreator->id, 'role' => 'captain', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $captainToken = $captain->createToken('auth')->plainTextToken;
        $scheduledAt = now()->addDays(2)->startOfHour()->toDateTimeString();

        $this->withToken($captainToken)
            ->postJson('/api/v1/auth/teams/'.$homeTeamId.'/match-requests', [
                'away_team_id' => $awayTeamId,
                'scheduled_at' => $scheduledAt,
                'venue' => 'Stade OSport',
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Demande de match envoyée.');

        $this->assertDatabaseHas('match_events', [
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'scheduled_at' => $scheduledAt,
            'status' => 'requested',
            'venue' => 'Stade OSport',
        ]);
    }

    public function test_match_request_is_rejected_when_teams_have_different_sports(): void
    {
        $captain = User::factory()->create();
        $awayCreator = User::factory()->create();
        $footballSportId = $this->sportIdBySlug('football');
        $basketballSportId = $this->sportIdBySlug('basketball');

        $homeTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $captain->id,
            'sport_id' => $footballSportId,
            'name' => 'Home Football Team',
            'slug' => 'home-football-team',
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

        $awayTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $awayCreator->id,
            'sport_id' => $basketballSportId,
            'name' => 'Away Basketball Team',
            'slug' => 'away-basketball-team',
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
            ['team_id' => $homeTeamId, 'user_id' => $captain->id, 'role' => 'captain', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $captainToken = $captain->createToken('auth')->plainTextToken;

        $this->withToken($captainToken)
            ->postJson('/api/v1/auth/teams/'.$homeTeamId.'/match-requests', [
                'away_team_id' => $awayTeamId,
                'scheduled_at' => now()->addDays(3)->toDateTimeString(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['away_team_id']);
    }

    public function test_match_request_is_rejected_if_pending_request_already_exists_between_two_teams(): void
    {
        $captain = User::factory()->create();
        $awayCreator = User::factory()->create();
        $sportId = $this->sportIdBySlug('football');

        $homeTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $captain->id,
            'sport_id' => $sportId,
            'name' => 'Pending Pair Home',
            'slug' => 'pending-pair-home',
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

        $awayTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $awayCreator->id,
            'sport_id' => $sportId,
            'name' => 'Pending Pair Away',
            'slug' => 'pending-pair-away',
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
            ['team_id' => $homeTeamId, 'user_id' => $captain->id, 'role' => 'captain', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('match_events')->insert([
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'scheduled_at' => now()->addDays(1)->toDateTimeString(),
            'venue' => null,
            'status' => 'requested',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $captainToken = $captain->createToken('auth')->plainTextToken;

        $this->withToken($captainToken)
            ->postJson('/api/v1/auth/teams/'.$homeTeamId.'/match-requests', [
                'away_team_id' => $awayTeamId,
                'scheduled_at' => now()->addDays(4)->toDateTimeString(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['away_team_id']);
    }

    public function test_match_request_requires_different_date_for_requesting_team_pending_requests(): void
    {
        $captain = User::factory()->create();
        $awayOneCreator = User::factory()->create();
        $awayTwoCreator = User::factory()->create();
        $sportId = $this->sportIdBySlug('football');
        $scheduledAt = now()->addDays(5)->startOfHour()->toDateTimeString();

        $homeTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $captain->id,
            'sport_id' => $sportId,
            'name' => 'Date Conflict Home',
            'slug' => 'date-conflict-home',
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

        $awayTeamOneId = DB::table('teams')->insertGetId([
            'creator_id' => $awayOneCreator->id,
            'sport_id' => $sportId,
            'name' => 'Date Conflict Away One',
            'slug' => 'date-conflict-away-one',
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

        $awayTeamTwoId = DB::table('teams')->insertGetId([
            'creator_id' => $awayTwoCreator->id,
            'sport_id' => $sportId,
            'name' => 'Date Conflict Away Two',
            'slug' => 'date-conflict-away-two',
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
            ['team_id' => $homeTeamId, 'user_id' => $captain->id, 'role' => 'captain', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('match_events')->insert([
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamOneId,
            'scheduled_at' => $scheduledAt,
            'venue' => null,
            'status' => 'requested',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $captainToken = $captain->createToken('auth')->plainTextToken;

        $this->withToken($captainToken)
            ->postJson('/api/v1/auth/teams/'.$homeTeamId.'/match-requests', [
                'away_team_id' => $awayTeamTwoId,
                'scheduled_at' => $scheduledAt,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['scheduled_at']);
    }

    public function test_captain_can_list_received_and_sent_match_requests(): void
    {
        $captain = User::factory()->create();
        $otherCreator = User::factory()->create();
        $sportId = $this->sportIdBySlug('football');

        $homeTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $captain->id,
            'sport_id' => $sportId,
            'name' => 'List Match Home',
            'slug' => 'list-match-home',
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

        $awayTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $otherCreator->id,
            'sport_id' => $sportId,
            'name' => 'List Match Away',
            'slug' => 'list-match-away',
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
            ['team_id' => $homeTeamId, 'user_id' => $captain->id, 'role' => 'captain', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['team_id' => $awayTeamId, 'user_id' => $otherCreator->id, 'role' => 'captain', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('match_events')->insert([
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'scheduled_at' => now()->addDay()->toDateTimeString(),
            'venue' => 'Arena One',
            'status' => 'requested',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('match_events')->insert([
            'home_team_id' => $awayTeamId,
            'away_team_id' => $homeTeamId,
            'scheduled_at' => now()->addDays(2)->toDateTimeString(),
            'venue' => 'Arena Two',
            'status' => 'requested',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('match_events')->insert([
            'home_team_id' => $awayTeamId,
            'away_team_id' => $homeTeamId,
            'scheduled_at' => now()->addDays(3)->toDateTimeString(),
            'venue' => 'Arena Three',
            'status' => 'scheduled',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $captainToken = $captain->createToken('auth')->plainTextToken;

        $this->withToken($captainToken)
            ->getJson('/api/v1/auth/teams/match-requests?type=sent')
            ->assertOk()
            ->assertJsonPath('data.type', 'sent')
            ->assertJsonCount(1, 'data.items');

        $this->withToken($captainToken)
            ->getJson('/api/v1/auth/teams/match-requests?type=received')
            ->assertOk()
            ->assertJsonPath('data.type', 'received')
            ->assertJsonCount(2, 'data.items')
            ->assertJsonStructure([
                'data' => [
                    'items' => [
                        '*' => [
                            'home_team' => [
                                'id',
                                'name',
                                'members' => [
                                    '*' => ['user_id', 'name', 'avatar_url', 'role'],
                                ],
                            ],
                            'away_team' => [
                                'id',
                                'name',
                                'members' => [
                                    '*' => ['user_id', 'name', 'avatar_url', 'role'],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        $this->withToken($captainToken)
            ->getJson('/api/v1/auth/teams/match-requests?type=received&status=accepted')
            ->assertOk()
            ->assertJsonPath('data.type', 'received')
            ->assertJsonPath('data.status', 'accepted')
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.status', 'accepted');

        $this->withToken($captainToken)
            ->getJson('/api/v1/auth/teams/match-requests?type=received&sport_name=Football')
            ->assertOk()
            ->assertJsonPath('data.type', 'received')
            ->assertJsonPath('data.sport_name', 'Football')
            ->assertJsonCount(2, 'data.items');
    }

    public function test_non_captain_non_creator_can_list_match_requests_with_management_flag_false(): void
    {
        $creator = User::factory()->create();
        $regularMember = User::factory()->create();
        $sportId = $this->sportIdBySlug('football');

        $teamId = DB::table('teams')->insertGetId([
            'creator_id' => $creator->id,
            'sport_id' => $sportId,
            'name' => 'Restricted Match List Team',
            'slug' => 'restricted-match-list-team',
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
            ['team_id' => $teamId, 'user_id' => $regularMember->id, 'role' => 'member', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $memberToken = $regularMember->createToken('auth')->plainTextToken;

        $this->withToken($memberToken)
            ->getJson('/api/v1/auth/teams/match-requests?type=received')
            ->assertOk()
            ->assertJsonPath('data.can_manage_match_requests', false)
            ->assertJsonCount(0, 'data.items');
    }

    public function test_receiver_can_accept_or_refuse_match_request(): void
    {
        $homeCaptain = User::factory()->create();
        $awayCaptain = User::factory()->create();
        $sportId = $this->sportIdBySlug('football');

        $homeTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $homeCaptain->id,
            'sport_id' => $sportId,
            'name' => 'Decision Home Team',
            'slug' => 'decision-home-team',
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

        $awayTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $awayCaptain->id,
            'sport_id' => $sportId,
            'name' => 'Decision Away Team',
            'slug' => 'decision-away-team',
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
            ['team_id' => $homeTeamId, 'user_id' => $homeCaptain->id, 'role' => 'captain', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['team_id' => $awayTeamId, 'user_id' => $awayCaptain->id, 'role' => 'captain', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $matchEventId = DB::table('match_events')->insertGetId([
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'scheduled_at' => now()->addDay()->toDateTimeString(),
            'venue' => null,
            'status' => 'requested',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $awayToken = $awayCaptain->createToken('auth')->plainTextToken;

        $this->withToken($awayToken)
            ->patchJson('/api/v1/auth/teams/match-requests/'.$matchEventId, [
                'decision' => 'accept',
            ])
            ->assertOk();

        $this->assertDatabaseHas('match_events', [
            'id' => $matchEventId,
            'status' => 'scheduled',
        ]);

        DB::table('match_events')->where('id', $matchEventId)->update([
            'status' => 'requested',
            'updated_at' => now(),
        ]);

        $this->withToken($awayToken)
            ->patchJson('/api/v1/auth/teams/match-requests/'.$matchEventId, [
                'decision' => 'refuse',
            ])
            ->assertOk();

        $this->assertDatabaseHas('match_events', [
            'id' => $matchEventId,
            'status' => 'cancelled',
        ]);
    }
}
