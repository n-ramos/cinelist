<?php

namespace App\Models;

use App\Enums\MediaType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'tmdb_id',
        'type',
        'title',
        'original_title',
        'overview',
        'poster_path',
        'backdrop_path',
        'release_date',
        'vote_average',
        'vote_count',
        'genre_ids',
        'runtime',
        'original_language',
        'popularity',
        'raw_data',
        'cached_at',
    ];

    public function casts(): array
    {
        return [
            'type' => MediaType::class,
            'release_date' => 'date',
            'vote_average' => 'float',
            'vote_count' => 'integer',
            'genre_ids' => 'array',
            'runtime' => 'integer',
            'popularity' => 'float',
            'raw_data' => 'array',
            'cached_at' => 'datetime',
        ];
    }

    public function userMovies(): HasMany
    {
        return $this->hasMany(UserMovie::class);
    }

    public function watchers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_movies')
            ->withPivot(['status', 'rating', 'notes', 'watched_at', 'proposed_by', 'priority'])
            ->withTimestamps();
    }

    public function scopeOfType(Builder $query, MediaType $type): Builder
    {
        return $query->where('type', $type->value);
    }

    public function posterUrl(string $size = 'w500'): ?string
    {
        if (! $this->poster_path) {
            return null;
        }

        return "https://image.tmdb.org/t/p/{$size}{$this->poster_path}";
    }

    public function backdropUrl(string $size = 'w1280'): ?string
    {
        if (! $this->backdrop_path) {
            return null;
        }

        return "https://image.tmdb.org/t/p/{$size}{$this->backdrop_path}";
    }

    public function year(): ?int
    {
        return $this->release_date?->year;
    }

    public function formattedRuntime(): ?string
    {
        if (! $this->runtime) {
            return null;
        }

        $hours = intdiv($this->runtime, 60);
        $minutes = $this->runtime % 60;

        if ($hours === 0) {
            return "{$minutes}min";
        }

        return $minutes > 0 ? "{$hours}h{$minutes}min" : "{$hours}h";
    }
}
