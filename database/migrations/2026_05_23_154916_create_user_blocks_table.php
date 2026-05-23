<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ce qu'elle fait : crée la table `user_blocks` qui matérialise la relation
 * « A a bloqué B » entre deux utilisateurs (schéma §5.6).
 *
 * Pourquoi : socle pour la fonctionnalité « comptes bloqués » de l'écran
 * Paramètres > Confidentialité (POST bloquer, GET lister, DELETE débloquer).
 * L'application du blocage côté feed/follow/messages se fait en couche service
 * dans les surfaces concernées (hors de cette migration).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('blocker_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('blocked_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['blocker_id', 'blocked_id']);
            $table->index('blocked_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_blocks');
    }
};
