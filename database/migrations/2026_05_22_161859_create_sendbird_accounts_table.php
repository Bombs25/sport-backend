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
        // Liaison 1:1 user <-> utilisateur Sendbird (schéma §1.6, option B).
        // Aucun token de session ici : généré à la demande côté backend.
        Schema::create('sendbird_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('sendbird_user_id')->unique();
            $table->timestamp('sendbird_synced_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sendbird_accounts');
    }
};
