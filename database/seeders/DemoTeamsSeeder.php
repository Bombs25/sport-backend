<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Ce qu’il fait : jusqu’à **20 équipes** de démonstration (`teams` + `team_members`), après `SportsSeeder` et des utilisateurs (ex. `DemoUsersSeeder`).
 *
 * Pourquoi : tester le flow liste / détail / effectifs sans créer les équipes à la main ; slugs stables `demo-seed-team-01` … pour rejouer le seeder sans doublons.
 */
class DemoTeamsSeeder extends Seeder
{
    private const MAX_TEAMS = 20;

    public function run(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('teams')) {
            return;
        }

        if (DB::table('teams')->where('slug', 'demo-seed-team-01')->exists()) {
            return;
        }

        $userIds = DB::table('users')
            ->orderBy('id')
            ->limit(500)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
        $sportIds = DB::table('sports')->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all();

        if ($userIds === [] || $sportIds === []) {
            return;
        }

        $teamCount = min(self::MAX_TEAMS, count($userIds));
        $now = now();

        $competitionTypes = ['leisure', 'competitive'];
        $skillLevels = ['beginner', 'intermediate', 'expert'];

        for ($i = 1; $i <= $teamCount; $i++) {
            $creatorId = $userIds[($i - 1) % count($userIds)];
            $sportId = $sportIds[($i - 1) % count($sportIds)];
            $name = sprintf('Équipe démo %02d', $i);
            $slug = sprintf('demo-seed-team-%02d', $i);
            $city = fake()->randomElement(['Paris', 'Lyon', 'Marseille', 'Lille', 'Bordeaux', 'Toulouse']);

            $teamId = DB::table('teams')->insertGetId([
                'creator_id' => $creatorId,
                'sport_id' => $sportId,
                'name' => $name,
                'slug' => $slug,
                'competition_type' => fake()->randomElement($competitionTypes),
                'skill_level' => fake()->randomElement($skillLevels),
                'description' => Str::limit(fake()->sentence(12), 200, ''),
                'hq_city' => $city,
                'hq_latitude' => fake()->randomFloat(7, 43.0, 50.0),
                'hq_longitude' => fake()->randomFloat(7, -1.5, 7.5),
                'cover_image_url' => null,
                'logo_url' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $memberRows = [[
                'team_id' => $teamId,
                'user_id' => $creatorId,
                'role' => 'captain',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]];

            $others = array_values(array_filter($userIds, static fn (int $id): bool => $id !== $creatorId));
            shuffle($others);
            $extraCap = min(count($others), fake()->numberBetween(2, 6));
            $extras = array_slice($others, 0, $extraCap);

            foreach ($extras as $userId) {
                $memberRows[] = [
                    'team_id' => $teamId,
                    'user_id' => $userId,
                    'role' => 'member',
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('team_members')->insert($memberRows);
        }
    }
}
