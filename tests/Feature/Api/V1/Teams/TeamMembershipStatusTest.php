<?php

namespace Tests\Feature\Api\V1\Teams;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeamMembershipStatusTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_membership_reports_pending_match_request_sent_by_viewer_team(): void
    {
        $captain = User::factory()->create();
        $opponentCaptain = User::factory()->create();
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
            'creator_id' => $opponentCaptain->id,
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
                'user_id' => $opponentCaptain->id,
                'role' => 'captain',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('match_events')->insert([
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'scheduled_at' => now()->addDays(3),
            'status' => 'requested',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $token = $captain->createToken('auth')->plainTextToken;

        $this->getJson('/api/v1/auth/teams/'.$awayTeamId.'/membership', [
            'Authorization' => 'Bearer '.$token,
        ])
            ->assertOk()
            ->assertJsonPath('data.match_request_pending', true)
            ->assertJsonPath('data.match_request_sent', true);
    }
}
