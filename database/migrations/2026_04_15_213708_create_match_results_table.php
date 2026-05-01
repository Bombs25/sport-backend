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
        Schema::create('match_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_event_id')->constrained('match_events')->cascadeOnDelete();
            $table->unsignedSmallInteger('home_score')->default(0);
            $table->unsignedSmallInteger('away_score')->default(0);
            $table->unsignedInteger('total_comments')->default(0);
            $table->unsignedInteger('total_likes')->default(0);
            $table->string('status', 32)->default('score_pending_validation')->index();
            $table->foreignId('submitted_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('responded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->text('refusal_reason')->nullable();
            $table->timestamps();

            $table->unique('match_event_id');
            $table->index(['submitted_by_user_id', 'status']);
            $table->index(['responded_by_user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_results');
    }
};
