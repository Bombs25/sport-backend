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
        Schema::create('response_commentaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained('comments')->cascadeOnDelete();
            $table->boolean('is_reponse_to_main_comment')->default(false);
            $table->text('response');
            $table->string('responded_to_who')->nullable()->default(null);
            $table->foreignId('users_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('likes_count')->default(0);
            $table->timestamps();

            $table->index(['comment_id']);
            // $table->index(['comment_id', 'users_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('response_commentaires');
    }
};
