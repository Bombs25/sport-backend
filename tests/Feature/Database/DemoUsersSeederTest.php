<?php

namespace Tests\Feature\Database;

use Database\Seeders\DemoUsersSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoUsersSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_demo_users_seeder_creates_at_least_twenty_users_with_profiles(): void
    {
        $this->seed(DemoUsersSeeder::class);

        $this->assertGreaterThanOrEqual(20, DB::table('users')->count());
        $this->assertGreaterThanOrEqual(20, DB::table('user_profiles')->count());
    }
}
