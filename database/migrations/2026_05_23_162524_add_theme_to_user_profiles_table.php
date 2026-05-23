<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ce qu'elle fait : ajoute `theme` (varchar 8, défaut `auto`) au profil.
 * Valeurs côté Form Request : `light | dark | auto`.
 *
 * Pourquoi : alimente l'écran Paramètres > Apparence. Sync inter-devices.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->string('theme', 8)->default('auto')->after('language');
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->dropColumn('theme');
        });
    }
};
