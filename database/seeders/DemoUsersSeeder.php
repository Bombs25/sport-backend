<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::factory()->count(20)->create();
        $now = now();

        foreach ($users as $index => $user) {
            $handle = 'user_'.($index + 1).'_'.Str::lower(Str::random(6));
            $city = fake()->randomElement(['Paris', 'Lyon', 'Marseille', 'Lille', 'Bordeaux']);

            DB::table('user_profiles')->updateOrInsert(
                ['user_id' => $user->id],
                [
                    'display_name' => Str::limit((string) ($user->name ?? 'Utilisateur '.$user->id), 64, ''),
                    'handle' => $handle,
                    'bio' => fake()->sentence(),
                    'avatar_url' => null,
                    'is_private' => false,
                    'latitude' => fake()->randomFloat(7, 43.0, 50.0),
                    'longitude' => fake()->randomFloat(7, -1.5, 7.5),
                    'city' => $city,
                    'address_line' => null,
                    'birth_date' => fake()->date('Y-m-d', '-18 years'),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $ids = $users->pluck('id')->values()->all();
        $follows = [];

        foreach ($ids as $followerId) {
            $targetIds = collect($ids)
                ->reject(fn (int $id): bool => $id === $followerId)
                ->shuffle()
                ->take(3)
                ->all();

            foreach ($targetIds as $followingId) {
                $follows[] = [
                    'follower_id' => $followerId,
                    'following_id' => $followingId,
                    'status' => 'accepted',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($follows !== [] && DB::getSchemaBuilder()->hasTable('follows')) {
            DB::table('follows')->upsert(
                $follows,
                ['follower_id', 'following_id'],
                ['status', 'updated_at']
            );
        }
    }
}
