<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('practice_type', 24)->default('individual')->index();
            $table->string('avatar', 512)->nullable();
            $table->timestamps();
        });

        $now = now();

        DB::table('sports')->insert([
            ['name' => 'Football', 'slug' => 'football', 'practice_type' => 'collective', 'avatar' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Basketball', 'slug' => 'basketball', 'practice_type' => 'collective', 'avatar' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tennis', 'slug' => 'tennis', 'practice_type' => 'individual', 'avatar' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Running', 'slug' => 'running', 'practice_type' => 'individual', 'avatar' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Yoga', 'slug' => 'yoga', 'practice_type' => 'individual', 'avatar' => null, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Padel', 'slug' => 'padel', 'practice_type' => 'collective', 'avatar' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sports');
    }
};
