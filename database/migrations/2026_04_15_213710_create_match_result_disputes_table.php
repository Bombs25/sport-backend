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
        Schema::create('match_result_disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_result_id')->constrained('match_results')->cascadeOnDelete();
            $table->foreignId('opened_by_user_id')->constrained('users')->restrictOnDelete();
            $table->boolean('dispute_reason_score_incorrect')->default(false);
            $table->boolean('dispute_reason_fair_play')->default(false);
            $table->boolean('dispute_reason_behavior')->default(false);
            $table->text('details');
            $table->string('evidence_path', 2048)->nullable();
            $table->string('evidence_disk', 32)->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->foreignId('moderator_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('moderator_notes')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['match_result_id', 'status']);
            $table->index(['opened_by_user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_result_disputes');
    }
};
