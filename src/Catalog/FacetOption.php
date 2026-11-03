<?php

declare(strict_types=1);

namespace App\Catalog;

/**
 * One value of a filter group: its URL value (a slug, an enum value), its name, how many
 * results it would give with the other groups applied, and whether it is ticked.
 */
final readonly class FacetOption
{
    public function __construct(
        public string $value,
        public string $label,
        public int $count,
        public bool $selected,
    ) {
    }
}
