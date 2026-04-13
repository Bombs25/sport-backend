<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ce qu’il fait : autorise `user_profiles.display_name` à être null tant que le profil public n’est pas finalisé.
     *
     * Pourquoi : `users.name` (nom d’état civil) reste **NOT NULL** (contrainte Laravel / produit) ; seul le libellé
     * affiché côté profil peut attendre l’étape dédiée si besoin. Le nom civil est désormais exigé dès `register/credentials`.
     */
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('display_name', 64)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('user_profiles')->whereNull('display_name')->update(['display_name' => '']);

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('display_name', 64)->nullable(false)->change();
        });
    }
};
