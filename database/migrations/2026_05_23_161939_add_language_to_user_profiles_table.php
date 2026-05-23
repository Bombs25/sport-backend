<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ce qu'elle fait : ajoute `language` (varchar 8, défaut `fr`) au profil
 * utilisateur. Valeurs validées côté Form Request : `fr | en`.
 *
 * Pourquoi : alimente l'écran Paramètres > Langue. Sync entre devices via
 * le serveur (le mobile gère aussi un cache MMKV pour accès immédiat).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->string('language', 8)->default('fr')->after('notification_preferences');
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->dropColumn('language');
        });
    }
};
