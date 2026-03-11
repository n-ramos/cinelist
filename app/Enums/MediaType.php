<?php

namespace App\Enums;

enum MediaType: string
{
    case Movie = 'movie';
    case TV = 'tv';

    public function label(): string
    {
        return match ($this) {
            self::Movie => 'Film',
            self::TV => 'Série',
        };
    }

    public function tmdbEndpoint(): string
    {
        return match ($this) {
            self::Movie => 'movie',
            self::TV => 'tv',
        };
    }
}
