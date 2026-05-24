<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `post_saves` — bookmark / « post enregistré » par un utilisateur.
 *
 * Pourquoi : feature « Save post » côté app (icône bookmark dans le footer
 * de chaque carte). Modèle polymorphique calqué sur `post_likes` :
 *   - `publication_id` + `publication_type ∈ {'regular','automatic'}`
 *   - 'regular'  → `posts.id`
 *   - 'automatic' → `match_results.id`
 * Privé à l'utilisateur (pas de notif à l'auteur, pas de compteur global).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_saves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('users_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('publication_id');
            $table->string('publication_type');
            $table->timestamps();

            // Un user ne sauve qu'une fois la même publication.
            $table->unique(['users_id', 'publication_id', 'publication_type'], 'post_saves_unique');
            // Liste « Enregistrés » d'un user (ORDER BY id DESC = chrono inverse).
            $table->index(['users_id', 'id']);
            // Enrichissement `viewer_has_saved` (JOIN dans FetchPostService).
            $table->index(['publication_id', 'publication_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_saves');
    }
};
