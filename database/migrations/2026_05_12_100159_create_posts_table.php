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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body')->nullable();
            $table->string('visibility', 24)->default('public');
            $table->string('status', 24)->default('published')->index();
            $table->unsignedTinyInteger('media_count')->default(0);
            $table->unsignedInteger('total_likes')->default(0);
            $table->unsignedInteger('total_comments')->default(0);
            $table->unsignedInteger('total_shares')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'published_at']);
            $table->index(['visibility', 'status', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
