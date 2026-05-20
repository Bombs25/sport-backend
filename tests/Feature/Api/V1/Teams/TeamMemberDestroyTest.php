<?php

namespace Tests\Feature\Api\V1\Teams;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeamMemberDestroyTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array{
     *     captain: User,
     *     member: User,
     *     creatorOnly: User,
     *     teamId: int,
     * }
     */
    private function seedTeamFixture(): array
    {
        $captain = User::factory()->create();
        $member = User::factory()->create();
        $creatorOnly = User::factory()->create();

        $now = now();
        $teamId = DB::table('teams')->insertGetId([
            'creator_id' => $creatorOnly->id,
            'sport_id' => 1,
            'name' => 'Equipe Destroy '.uniqid('', true),
            'slug' => 'equipe-destroy-'.uniqid('', true),
            'competition_type' => 'leisure',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('team_members')->insert([
            [
                'team_id' => $teamId,
                'user_id' => $captain->id,
                'role' => 'captain',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'team_id' => $teamId,
                'user_id' => $member->id,
                'role' => 'member',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'team_id' => $teamId,
                'user_id' => $creatorOnly->id,
                'role' => 'member',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        return [
            'captain' => $captain,
            'member' => $member,
            'creatorOnly' => $creatorOnly,
            'teamId' => $teamId,
        ];
    }

    public function test_member_can_leave_team_themselves(): void
    {
        $fixture = $this->seedTeamFixture();
        $token = $fixture['member']->createToken('auth')->plainTextToken;

        $this->deleteJson(
            '/api/v1/auth/teams/'.$fixture['teamId'].'/members/'.$fixture['member']->id,
            [],
            ['Authorization' => 'Bearer '.$token],
        )
            ->assertOk()
            ->assertJsonPath('message', 'Sortie de l’équipe effectuée.');

        $this->assertDatabaseHas('team_members', [
            'team_id' => $fixture['teamId'],
            'user_id' => $fixture['member']->id,
            'status' => 'left',
        ]);
    }

    public function test_active_captain_can_remove_member(): void
    {
        $fixture = $this->seedTeamFixture();
        $token = $fixture['captain']->createToken('auth')->plainTextToken;

        $this->deleteJson(
            '/api/v1/auth/teams/'.$fixture['teamId'].'/members/'.$fixture['member']->id,
            [],
            ['Authorization' => 'Bearer '.$token],
        )
            ->assertOk()
            ->assertJsonPath('message', 'Membre supprimé de l’équipe.');

        $this->assertDatabaseHas('team_members', [
            'team_id' => $fixture['teamId'],
            'user_id' => $fixture['member']->id,
            'status' => 'left',
        ]);
    }

    public function test_creator_without_captain_role_cannot_remove_member(): void
    {
        $fixture = $this->seedTeamFixture();
        $token = $fixture['creatorOnly']->createToken('auth')->plainTextToken;

        $this->deleteJson(
            '/api/v1/auth/teams/'.$fixture['teamId'].'/members/'.$fixture['member']->id,
            [],
            ['Authorization' => 'Bearer '.$token],
        )->assertForbidden();

        $this->assertDatabaseHas('team_members', [
            'team_id' => $fixture['teamId'],
            'user_id' => $fixture['member']->id,
            'status' => 'active',
        ]);
    }

    public function test_regular_member_cannot_remove_another_member(): void
    {
        $fixture = $this->seedTeamFixture();

        $otherMember = User::factory()->create();
        $now = now();
        DB::table('team_members')->insert([
            'team_id' => $fixture['teamId'],
            'user_id' => $otherMember->id,
            'role' => 'member',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $token = $fixture['member']->createToken('auth')->plainTextToken;

        $this->deleteJson(
            '/api/v1/auth/teams/'.$fixture['teamId'].'/members/'.$otherMember->id,
            [],
            ['Authorization' => 'Bearer '.$token],
        )->assertForbidden();
    }

    public function test_cannot_remove_team_creator(): void
    {
        $fixture = $this->seedTeamFixture();
        $token = $fixture['captain']->createToken('auth')->plainTextToken;

        $this->deleteJson(
            '/api/v1/auth/teams/'.$fixture['teamId'].'/members/'.$fixture['creatorOnly']->id,
            [],
            ['Authorization' => 'Bearer '.$token],
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['member_user_id']);
    }

    public function test_cannot_remove_non_active_member(): void
    {
        $fixture = $this->seedTeamFixture();
        $leftUser = User::factory()->create();
        $now = now();

        DB::table('team_members')->insert([
            'team_id' => $fixture['teamId'],
            'user_id' => $leftUser->id,
            'role' => 'member',
            'status' => 'left',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $token = $fixture['captain']->createToken('auth')->plainTextToken;

        $this->deleteJson(
            '/api/v1/auth/teams/'.$fixture['teamId'].'/members/'.$leftUser->id,
            [],
            ['Authorization' => 'Bearer '.$token],
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['member_user_id']);
    }

    public function test_returns_404_for_unknown_team(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $this->deleteJson(
            '/api/v1/auth/teams/999999/members/'.$user->id,
            [],
            ['Authorization' => 'Bearer '.$token],
        )->assertNotFound();
    }
}
