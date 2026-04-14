<?php

namespace Tests\Feature\Database;

use Database\Seeders\DemoTeamsSeeder;
use Database\Seeders\DemoUsersSeeder;
use Database\Seeders\SportsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoTeamsSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_demo_teams_seeder_creates_up_to_twenty_teams_with_members(): void
    {
        $this->seed(SportsSeeder::class);
        $this->seed(DemoUsersSeeder::class);
        $this->seed(DemoTeamsSeeder::class);

        $teamsCount = DB::table('teams')->where('slug', 'like', 'demo-seed-team-%')->count();
        $this->assertSame(20, $teamsCount);

        $this->assertGreaterThanOrEqual(20, DB::table('team_members')->where('role', 'captain')->count());

        foreach (range(1, 20) as $n) {
            $slug = sprintf('demo-seed-team-%02d', $n);
            $teamId = DB::table('teams')->where('slug', $slug)->value('id');
            $this->assertNotNull($teamId);
            $this->assertGreaterThanOrEqual(3, DB::table('team_members')->where('team_id', $teamId)->count());
        }
    }
}
