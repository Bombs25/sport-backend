<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('sport_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('victory_count')->default(0);
            $table->unsignedInteger('draw_count')->default(0);
            $table->unsignedInteger('defeat_count')->default(0);
            $table->unsignedInteger('point_count')->default(0);
            $table->timestamps();

            $table->unique(['team_id', 'sport_id', 'created_at']);
            $table->index(['sport_id', 'created_at', 'point_count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stats');
    }
};
