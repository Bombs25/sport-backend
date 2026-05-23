<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ce qu'elle fait : ajoute les champs de confidentialité granulaire au profil :
 *  - `who_can_tag_me` (everyone | followers | nobody) — défaut everyone
 *  - `who_can_message_me` (everyone | followers | nobody) — défaut everyone
 *  - `precise_location_enabled` (bool) — défaut true
 *  - `hide_online_status` (bool) — défaut false
 *
 * Pourquoi : alimente l'écran Paramètres > Confidentialité (toggles + selectors).
 * Stockage en `string(16)` plutôt qu'enum natif MySQL pour rester flexible côté
 * application (valeurs validées par Form Request).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->string('who_can_tag_me', 16)->default('everyone')->after('is_private');
            $table->string('who_can_message_me', 16)->default('everyone')->after('who_can_tag_me');
            $table->boolean('precise_location_enabled')->default(true)->after('who_can_message_me');
            $table->boolean('hide_online_status')->default(false)->after('precise_location_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->dropColumn([
                'who_can_tag_me',
                'who_can_message_me',
                'precise_location_enabled',
                'hide_online_status',
            ]);
        });
    }
};
