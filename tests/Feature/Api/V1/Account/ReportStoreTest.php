<?php

namespace Tests\Feature\Api\V1\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportStoreTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('auth')->plainTextToken;
    }

    public function test_report_creates_row_and_returns_201(): void
    {
        $reporter = User::factory()->create();
        $reported = User::factory()->create();
        $token = $this->tokenFor($reporter);

        $this->postJson('/api/v1/auth/reports', [
            'reported_user_id' => $reported->id,
            'reason' => 'harassment',
            'details' => 'Messages insultants en boucle.',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(201)
            ->assertJson(['reported' => true, 'created' => true])
            ->assertJsonStructure(['report_id']);

        $this->assertDatabaseHas('user_reports', [
            'reporter_id' => $reporter->id,
            'reported_user_id' => $reported->id,
            'reason' => 'harassment',
            'status' => 'pending',
        ]);
    }

    public function test_report_is_idempotent_while_previous_still_pending(): void
    {
        $reporter = User::factory()->create();
        $reported = User::factory()->create();
        $token = $this->tokenFor($reporter);

        $existingId = DB::table('user_reports')->insertGetId([
            'reporter_id' => $reporter->id,
            'reported_user_id' => $reported->id,
            'reason' => 'spam',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/v1/auth/reports', [
            'reported_user_id' => $reported->id,
            'reason' => 'harassment',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJson([
                'reported' => true,
                'created' => false,
                'report_id' => $existingId,
            ]);

        $this->assertSame(1, DB::table('user_reports')
            ->where('reporter_id', $reporter->id)
            ->where('reported_user_id', $reported->id)
            ->count());
    }

    public function test_report_allows_new_entry_when_previous_was_resolved(): void
    {
        $reporter = User::factory()->create();
        $reported = User::factory()->create();
        $token = $this->tokenFor($reporter);

        DB::table('user_reports')->insert([
            'reporter_id' => $reporter->id,
            'reported_user_id' => $reported->id,
            'reason' => 'spam',
            'status' => 'resolved',
            'resolved_at' => now(),
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $this->postJson('/api/v1/auth/reports', [
            'reported_user_id' => $reported->id,
            'reason' => 'harassment',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertStatus(201)
            ->assertJson(['created' => true]);

        $this->assertSame(2, DB::table('user_reports')
            ->where('reporter_id', $reporter->id)
            ->where('reported_user_id', $reported->id)
            ->count());
    }

    public function test_report_rejects_self(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $this->postJson('/api/v1/auth/reports', [
            'reported_user_id' => $user->id,
            'reason' => 'spam',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['reported_user_id']);
    }

    public function test_report_rejects_unknown_reason(): void
    {
        $reporter = User::factory()->create();
        $reported = User::factory()->create();
        $token = $this->tokenFor($reporter);

        $this->postJson('/api/v1/auth/reports', [
            'reported_user_id' => $reported->id,
            'reason' => 'not_a_real_reason',
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_report_requires_authentication(): void
    {
        $reported = User::factory()->create();

        $this->postJson('/api/v1/auth/reports', [
            'reported_user_id' => $reported->id,
            'reason' => 'spam',
        ])->assertUnauthorized();
    }
}
