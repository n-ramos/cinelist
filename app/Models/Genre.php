<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Genre extends Model
{
    use HasFactory;

    protected $fillable = [
        'tmdb_id',
        'name',
        'type',
    ];

    public function casts(): array
    {
        return [
            'tmdb_id' => 'integer',
        ];
    }
}
