<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tmdb_id')->unique()->index();
            $table->string('type')->default('movie'); // movie or tv
            $table->string('title');
            $table->string('original_title')->nullable();
            $table->text('overview')->nullable();
            $table->string('poster_path')->nullable();
            $table->string('backdrop_path')->nullable();
            $table->date('release_date')->nullable();
            $table->decimal('vote_average', 4, 2)->default(0);
            $table->unsignedInteger('vote_count')->default(0);
            $table->json('genre_ids')->nullable();
            $table->unsignedSmallInteger('runtime')->nullable();
            $table->string('original_language', 10)->nullable();
            $table->decimal('popularity', 10, 3)->default(0);
            $table->json('raw_data')->nullable();
            $table->timestamp('cached_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
