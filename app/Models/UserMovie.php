<?php

namespace App\Models;

use App\Enums\MovieStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMovie extends Model
{
    protected $fillable = [
        'user_id',
        'movie_id',
        'status',
        'rating',
        'notes',
        'watched_at',
        'proposed_by',
        'priority',
    ];

    public function casts(): array
    {
        return [
            'status' => MovieStatus::class,
            'rating' => 'integer',
            'watched_at' => 'datetime',
            'priority' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeWithStatus(Builder $query, MovieStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }
}
