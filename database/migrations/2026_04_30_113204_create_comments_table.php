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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->text('content');
            $table->unsignedBigInteger('publication_id');
            $table->string('publication_type');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('responses_count')->default(0);
            $table->unsignedInteger('likes_count')->default(0);
            $table->timestamps();

            $table->index(['publication_id', 'publication_type']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
