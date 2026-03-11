<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_movies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('watchlist'); // watchlist, watched, proposed, dismissed
            $table->unsignedTinyInteger('rating')->nullable(); // 1-10
            $table->text('notes')->nullable();
            $table->timestamp('watched_at')->nullable();
            $table->string('proposed_by')->nullable(); // name of person who proposed
            $table->unsignedTinyInteger('priority')->default(5); // 1-10, higher = more priority
            $table->timestamps();

            $table->unique(['user_id', 'movie_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_movies');
    }
};
