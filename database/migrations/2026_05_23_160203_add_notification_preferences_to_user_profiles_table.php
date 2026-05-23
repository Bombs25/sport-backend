<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ce qu'elle fait : ajoute une colonne JSON `notification_preferences` au
 * profil utilisateur. Structure attendue (validée côté Form Request) :
 *
 *   {
 *     "channels": { "push": bool, "email": bool, "sms": bool },
 *     "social":   { "mentions": bool, "likes": bool, "comments": bool, "follow": bool },
 *     "teams":    { "ranking": bool, "trophies": bool, "member_changes": bool },
 *     "matches":  { "requests": bool, "reminders": bool, "score": bool, "end": bool },
 *     "messaging":{ "direct": bool, "media": bool }
 *   }
 *
 * Pourquoi : alimente l'écran Paramètres > Notifications (sections « Activité »
 * + « Mode de réception ») et permet à `NotificationPreferencesService` de
 * filtrer les push avant envoi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->json('notification_preferences')->nullable()->after('hide_online_status');
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->dropColumn('notification_preferences');
        });
    }
};
