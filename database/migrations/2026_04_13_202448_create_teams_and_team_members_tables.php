<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sport_id')->constrained()->restrictOnDelete();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('competition_type', 24)->default('leisure');
            $table->string('skill_level', 32)->nullable();
            $table->text('description')->nullable();
            $table->string('hq_city', 120)->nullable()->index();
            $table->decimal('hq_latitude', 10, 7)->nullable();
            $table->decimal('hq_longitude', 10, 7)->nullable();
            $table->string('cover_image_url', 512)->nullable();
            $table->string('logo_url', 512)->nullable();
            $table->timestamps();

            $table->index(['sport_id', 'created_at']);
            $table->index(['creator_id', 'created_at']);
            $table->index(['sport_id', 'competition_type', 'created_at']);
            $table->index(['sport_id', 'hq_city']);
        });

        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 32)->default('member');
            $table->string('status', 24)->default('active');
            $table->timestamps();

            $table->unique(['team_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('teams');
    }
};
