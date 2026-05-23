<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée `user_reports` : signalement d'un utilisateur par un autre.
     *
     * Pourquoi : remplacer le simple bloc (qui se contente de couper la relation)
     * par un canal de modération — table source pour une future file admin.
     * Un seul signalement actif par couple (reporter_id, reported_user_id) :
     * si l'utilisateur veut renvoyer un nouveau motif, le précédent doit être
     * `resolved` ou `dismissed` (logique côté service).
     */
    public function up(): void
    {
        Schema::create('user_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reported_user_id')->constrained('users')->cascadeOnDelete();
            // Motifs alignés sur l'UX mobile (ActionSheet > Signaler).
            $table->string('reason', 64);
            $table->text('details')->nullable();
            $table->string('status', 32)->default('pending')->index();
            // pending | under_review | resolved | dismissed
            $table->foreignId('moderator_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('moderator_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['reported_user_id', 'status']);
            $table->index(['reporter_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_reports');
    }
};
