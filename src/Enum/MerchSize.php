<?php

declare(strict_types=1);

namespace App\Enum;

enum MerchSize: string
{
    case XS = 'xs';
    case S = 's';
    case M = 'm';
    case L = 'l';
    case XL = 'xl';
    case XXL = 'xxl';
    case ONE_SIZE = 'one_size';

    public function label(): string
    {
        return match ($this) {
            self::ONE_SIZE => 'Taille unique',
            default => strtoupper($this->value),
        };
    }
}
