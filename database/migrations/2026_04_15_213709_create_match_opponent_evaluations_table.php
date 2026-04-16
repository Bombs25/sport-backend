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
        Schema::create('match_opponent_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_result_id')->constrained('match_results')->cascadeOnDelete();
            $table->foreignId('evaluator_team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('evaluator_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('evaluated_team_id')->constrained('teams')->cascadeOnDelete();
            $table->unsignedTinyInteger('fair_play_rating');
            $table->unsignedTinyInteger('punctuality_rating');
            $table->text('remarks')->nullable();
            $table->timestamps();

            // Nom explicite : MySQL limite les identifiants à 64 caractères (nom auto-généré trop long).
            $table->unique(['match_result_id', 'evaluator_team_id'], 'moe_result_eval_team_uidx');
            $table->index(['evaluated_team_id', 'created_at'], 'moe_evaluated_team_created_idx');
            $table->index(['evaluator_team_id', 'created_at'], 'moe_evaluator_team_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_opponent_evaluations');
    }
};
