<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('match_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('away_team_id')->constrained('teams')->cascadeOnDelete();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->string('venue', 255)->nullable();
            $table->string('status', 32)->default('scheduled')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['home_team_id', 'status', 'scheduled_at']);
            $table->index(['away_team_id', 'status', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_events');
    }
};
