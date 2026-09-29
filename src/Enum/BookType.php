<?php

declare(strict_types=1);

namespace App\Enum;

enum BookType: string
{
    case FANZINE = 'fanzine';
    case BOOK = 'book';

    public function label(): string
    {
        return match ($this) {
            self::FANZINE => 'Fanzine',
            self::BOOK => 'Livre',
        };
    }
}
