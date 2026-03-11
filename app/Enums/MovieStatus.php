<?php

namespace App\Enums;

enum MovieStatus: string
{
    case Watchlist = 'watchlist';
    case Watched = 'watched';
    case Proposed = 'proposed';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Watchlist => 'À voir',
            self::Watched => 'Vu',
            self::Proposed => 'Proposé',
            self::Dismissed => 'Ignoré',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Watchlist => 'blue',
            self::Watched => 'green',
            self::Proposed => 'amber',
            self::Dismissed => 'zinc',
        };
    }
}
