<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SportsSeeder extends Seeder
{
    /**
     * @return list<array{name: string, slug: string, practice_type: string}>
     */
    private function definitions(): array
    {
        return [
            ['name' => 'Football', 'slug' => 'football', 'practice_type' => 'collective'],
            ['name' => 'Basketball', 'slug' => 'basketball', 'practice_type' => 'collective'],
            ['name' => 'Tennis', 'slug' => 'tennis', 'practice_type' => 'individual'],
            ['name' => 'Running', 'slug' => 'running', 'practice_type' => 'individual'],
            ['name' => 'Yoga', 'slug' => 'yoga', 'practice_type' => 'individual'],
            ['name' => 'Padel', 'slug' => 'padel', 'practice_type' => 'collective'],
        ];
    }

    public function run(): void
    {
        $now = now();

        foreach ($this->definitions() as $row) {
            DB::table('sports')->updateOrInsert(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'practice_type' => $row['practice_type'],
                    'avatar' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
