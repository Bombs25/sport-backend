<?php

namespace Database\Seeders;

use App\Services\Search\TypesenseUserService;
use App\Support\UserProfileLocation;
use Carbon\CarbonInterface;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Typesense\Exceptions\TypesenseClientError;

class DemoUsersSeeder extends Seeder
{
    private const INSERT_CHUNK = 1000;

    private const FOLLOW_INSERT_CHUNK = 500;

    /** @var list<string> */
    private const SKILL_LEVELS = ['beginner', 'intermediate', 'expert'];

    private const NAME_POOL_SIZE = 2000;

    /**
     * Centres-ville approximatifs (WGS-84), France métropolitaine.
     *
     * @var list<array{name: string, latitude: float, longitude: float}>
     */
    private const FRENCH_CITIES = [
        ['name' => 'Paris', 'latitude' => 48.8566, 'longitude' => 2.3522],
        ['name' => 'Lyon', 'latitude' => 45.7640, 'longitude' => 4.8357],
        ['name' => 'Marseille', 'latitude' => 43.2965, 'longitude' => 5.3698],
        ['name' => 'Lille', 'latitude' => 50.6292, 'longitude' => 3.0573],
        ['name' => 'Bordeaux', 'latitude' => 44.8378, 'longitude' => -0.5792],
        ['name' => 'Toulouse', 'latitude' => 43.6047, 'longitude' => 1.4442],
        ['name' => 'Nice', 'latitude' => 43.7102, 'longitude' => 7.2620],
        ['name' => 'Nantes', 'latitude' => 47.2184, 'longitude' => -1.5536],
        ['name' => 'Strasbourg', 'latitude' => 48.5734, 'longitude' => 7.7521],
        ['name' => 'Montpellier', 'latitude' => 43.6108, 'longitude' => 3.8767],
        ['name' => 'Rennes', 'latitude' => 48.1173, 'longitude' => -1.6778],
        ['name' => 'Reims', 'latitude' => 49.2583, 'longitude' => 4.0317],
        ['name' => 'Le Havre', 'latitude' => 49.4944, 'longitude' => 0.1079],
        ['name' => 'Saint-Étienne', 'latitude' => 45.4397, 'longitude' => 4.3872],
        ['name' => 'Toulon', 'latitude' => 43.1242, 'longitude' => 5.9280],
        ['name' => 'Grenoble', 'latitude' => 45.1885, 'longitude' => 5.7245],
        ['name' => 'Dijon', 'latitude' => 47.3220, 'longitude' => 5.0415],
        ['name' => 'Angers', 'latitude' => 47.4739, 'longitude' => -0.5632],
        ['name' => 'Clermont-Ferrand', 'latitude' => 45.7772, 'longitude' => 3.0869],
        ['name' => 'Tours', 'latitude' => 47.3941, 'longitude' => 0.6944],
    ];

    /** @var array{name: string, latitude: float, longitude: float} */
    private const LISIEUX = ['name' => 'Lisieux', 'latitude' => 49.1469, 'longitude' => 0.2271];

    public function run(): void
    {
        $totalUsers = max(1, min(10_000_000, (int) env('DEMO_USERS_COUNT', 15_000)));
        $now = now();
        $passwordHash = Hash::make('jimmyBulL1230$');
        $namePool = $this->buildNamePool();

        $sportIds = DB::table('sports')->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $shouldSeedFollows = filter_var(
            env('DEMO_USERS_SEED_FOLLOWS', $totalUsers <= 20_000 ? '1' : '0'),
            FILTER_VALIDATE_BOOL
        );

        $typesense = app(TypesenseUserService::class);

        try {
            $typesense->ensureCollection();
        } catch (TypesenseClientError $e) {
            $this->command?->warn('Typesense indisponible, import ignoré : '.$e->getMessage());
            $typesense = null;
        }

        for ($base = 0; $base < $totalUsers; $base += self::INSERT_CHUNK) {
            $chunkSize = min(self::INSERT_CHUNK, $totalUsers - $base);

            $userRows = [];
            for ($i = 0; $i < $chunkSize; $i++) {
                $n = $base + $i + 1;
                $fullName = $this->demoFullName($n, $namePool);

                $userRows[] = [
                    'name' => $fullName,
                    'email' => "demo.user.{$n}@seed.osport.test",
                    'email_verified_at' => $now,
                    'password' => $passwordHash,
                    'remember_token' => Str::random(10),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $idBefore = (int) (DB::table('users')->max('id') ?? 0);
            DB::table('users')->insert($userRows);
            $userIds = DB::table('users')
                ->where('id', '>', $idBefore)
                ->orderBy('id')
                ->limit($chunkSize)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();
            if (count($userIds) !== $chunkSize) {
                throw new \RuntimeException('DemoUsersSeeder: nombre d’utilisateurs inséré incohérent (attendu '.$chunkSize.', obtenu '.count($userIds).').');
            }

            $profileRows = [];
            $typesenseDocs = [];
            foreach ($userIds as $offset => $userId) {
                $n = $base + $offset + 1;
                $place = $this->frenchPlaceForDemoUser($userId);
                $fullName = $this->demoFullName($n, $namePool);
                $displayName = Str::limit($fullName, 64, '');
                $handle = $this->deterministicHandle($n);
                $bio = 'Profil démo n°'.$n.'.';

                $profileRows[] = array_merge([
                    'user_id' => $userId,
                    'display_name' => $displayName,
                    'handle' => $handle,
                    'bio' => $bio,
                    'is_private' => false,
                    'city' => $place['name'],
                    'address_line' => null,
                    'birth_date' => $this->deterministicBirthDate($n),
                    'created_at' => $now,
                    'updated_at' => $now,
                ], UserProfileLocation::columnsFromLatLng(
                    $place['latitude'],
                    $place['longitude'],
                ));

                $typesenseDocs[] = [
                    'id' => (string) $userId,
                    'name' => $fullName,
                    'email' => "demo.user.{$n}@seed.osport.test",
                    'display_name' => $displayName,
                    'handle' => $handle,
                    'bio' => $bio,
                    'is_private' => false,
                    'city' => $place['name'],
                    'location' => [$place['latitude'], $place['longitude']],
                    'created_at' => $now->timestamp,
                ];
            }

            DB::table('user_profiles')->insert($profileRows);

            if ($typesense !== null) {
                try {
                    $typesense->importDocuments($typesenseDocs);
                } catch (TypesenseClientError $e) {
                    $this->command?->warn('Typesense import échoué (chunk '.($base + 1).') : '.$e->getMessage());
                }
            }

            if ($sportIds !== []) {
                $sportRows = $this->buildUserSportsRows($userIds, $sportIds, $base, $now);
                foreach (array_chunk($sportRows, self::INSERT_CHUNK) as $sportChunk) {
                    DB::table('user_sports')->insert($sportChunk);
                }
            }

            if ($this->command !== null && ($base + $chunkSize) % 100_000 === 0) {
                $this->command->info('Demo users: '.($base + $chunkSize).' / '.$totalUsers);
            }
        }

        if ($shouldSeedFollows && $totalUsers >= 2 && DB::getSchemaBuilder()->hasTable('follows')) {
            $this->seedFollowsInBatches($totalUsers, $now);
        }
    }

    /**
     * @return array{name: string, latitude: float, longitude: float}
     */
    private function frenchPlaceForDemoUser(int $userId): array
    {
        if ($userId === 1) {
            return self::LISIEUX;
        }

        $idx = ($userId - 1) % count(self::FRENCH_CITIES);

        return self::FRENCH_CITIES[$idx];
    }

    private function deterministicHandle(int $n): string
    {
        $suffix = substr(md5((string) $n), 0, 6);

        return Str::limit('u_'.$n.'_'.$suffix, 32, '');
    }

    /**
     * @return list<string>
     */
    private function buildNamePool(): array
    {
        $faker = FakerFactory::create('fr_FR');
        $faker->seed(42);

        $names = [];
        while (count($names) < self::NAME_POOL_SIZE) {
            $fullName = $faker->firstName().' '.$faker->lastName();
            $names[$fullName] = $fullName;
        }

        return array_values($names);
    }

    /**
     * @param  list<string>  $namePool
     */
    private function demoFullName(int $n, array $namePool): string
    {
        return $namePool[($n - 1) % count($namePool)];
    }

    private function deterministicBirthDate(int $n): string
    {
        $year = 1985 + ($n % 25);
        $month = ($n % 12) + 1;
        $day = min(28, ($n % 28) + 1);

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    /**
     * @param  array<int, int>  $userIds
     * @param  array<int, int>  $sportIds
     * @return list<array<string, mixed>>
     */
    private function buildUserSportsRows(array $userIds, array $sportIds, int $baseOffset, CarbonInterface $now): array
    {
        $rows = [];
        $maxPick = min(3, count($sportIds));

        foreach ($userIds as $offset => $userId) {
            $n = $baseOffset + $offset + 1;
            $pickCount = ($n % $maxPick) + 1;
            $chosen = $this->deterministicSportPick($sportIds, $n, $pickCount);

            foreach ($chosen as $j => $sportId) {
                $rows[] = [
                    'user_id' => $userId,
                    'sport_id' => $sportId,
                    'is_favorite' => $j === 0,
                    'skill_level' => self::SKILL_LEVELS[$n % count(self::SKILL_LEVELS)],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  array<int, int>  $sportIds
     * @return list<int>
     */
    private function deterministicSportPick(array $sportIds, int $seed, int $pickCount): array
    {
        $indexed = array_values($sportIds);
        $len = count($indexed);
        if ($len === 0) {
            return [];
        }

        $pickCount = min($pickCount, $len);
        $used = [];
        $out = [];
        $i = 0;

        while (count($out) < $pickCount && $i < $len * 4) {
            $pos = ($seed + $i * 17) % $len;
            $i++;
            if (isset($used[$pos])) {
                continue;
            }
            $used[$pos] = true;
            $out[] = $indexed[$pos];
        }

        return $out;
    }

    private function seedFollowsInBatches(int $totalUsers, CarbonInterface $now): void
    {
        $followBatch = 2000;
        $perUser = 3;
        $buffer = [];

        for ($followerId = 1; $followerId <= $totalUsers; $followerId++) {
            $targets = $this->pickFollowingIds($followerId, $totalUsers, $perUser);

            foreach ($targets as $followingId) {
                $buffer[] = [
                    'follower_id' => $followerId,
                    'following_id' => $followingId,
                    'status' => 'accepted',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (count($buffer) >= self::FOLLOW_INSERT_CHUNK) {
                DB::table('follows')->upsert(
                    $buffer,
                    ['follower_id', 'following_id'],
                    ['status', 'updated_at']
                );
                $buffer = [];
            }
        }

        if ($buffer !== []) {
            foreach (array_chunk($buffer, self::FOLLOW_INSERT_CHUNK) as $chunk) {
                DB::table('follows')->upsert(
                    $chunk,
                    ['follower_id', 'following_id'],
                    ['status', 'updated_at']
                );
            }
        }
    }

    /**
     * @return list<int>
     */
    private function pickFollowingIds(int $followerId, int $totalUsers, int $count): array
    {
        $picked = [];
        $attempts = 0;
        $maxAttempts = max(50, $count * 25);

        while (count($picked) < $count && $attempts < $maxAttempts) {
            $attempts++;
            $candidate = random_int(1, $totalUsers);
            if ($candidate === $followerId) {
                continue;
            }
            if (in_array($candidate, $picked, true)) {
                continue;
            }
            $picked[] = $candidate;
        }

        return $picked;
    }
}
