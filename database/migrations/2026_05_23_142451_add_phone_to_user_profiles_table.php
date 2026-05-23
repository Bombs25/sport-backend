<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ce qu'elle fait : ajoute un numéro de téléphone optionnel au profil utilisateur.
 *
 * Pourquoi : champ exposé dans l'écran Paramètres > Informations personnelles ;
 * sert au contact (support, double facteur futur). Nullable et longueur 32 pour
 * accepter les formats E.164 avec préfixe international.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->string('phone', 32)->nullable()->after('handle');
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->dropColumn('phone');
        });
    }
};
