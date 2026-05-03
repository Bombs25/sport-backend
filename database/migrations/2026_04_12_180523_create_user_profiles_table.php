<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('display_name', 64)->index();
            $table->string('handle', 32)->unique();
            $table->text('bio')->nullable();
            $table->string('avatar_url', 512)->nullable();
            $table->boolean('is_private')->default(false);
            $table->geography('location', subtype: 'point', srid: 4326)->nullable();
            $table->string('city', 120)->nullable()->index();
            $table->timestamps();
            $table->index('user_id');
            $table->index('handle');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
