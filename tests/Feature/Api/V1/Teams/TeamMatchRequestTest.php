<?php

namespace Tests\Feature\Api\V1\Teams;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeamMatchRequestTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_cannot_request_match_against_team_user_is_active_member_of(): void
    {
        $captain = User::factory()->create();
        $now = now();

        $homeTeamId = DB::table('teams')->insertGetId([
            'creator_id' => $captain->id,
            'sport_id' => 1,
            'name' => 'Home Team '.uniqid('', true),
            'slug' => 'home-team-'.uniqid('', true),
            'competition_type' => 'leisure',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $awayTeamId = DB::table('teams')->insertGetId([
            'creator_id' => User::factory()->create()->id,
            'sport_id' => 1,
            'name' => 'Away Team '.uniqid('', true),
            'slug' => 'away-team-'.uniqid('', true),
            'competition_type' => 'leisure',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('team_members')->insert([
            [
                'team_id' => $homeTeamId,
                'user_id' => $captain->id,
                'role' => 'captain',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'team_id' => $awayTeamId,
                'user_id' => $captain->id,
                'role' => 'member',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $token = $captain->createToken('auth')->plainTextToken;

        $this->postJson('/api/v1/auth/teams/'.$homeTeamId.'/match-requests', [
            'away_team_id' => $awayTeamId,
            'scheduled_at' => now()->addDays(5)->toIso8601String(),
        ], [
            'Authorization' => 'Bearer '.$token,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['away_team_id']);
    }
}
