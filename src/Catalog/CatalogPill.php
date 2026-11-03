<?php

declare(strict_types=1);

namespace App\Catalog;

/**
 * An active filter above the results: what it is, and the page without it.
 */
final readonly class CatalogPill
{
    public function __construct(
        public CatalogFacet $group,
        public string $label,
        public CatalogFilters $without,
        public string $url,
    ) {
    }

    /** « Style : Punk », « Nos productions ». */
    public function name(): string
    {
        return $this->group->isSwitch() ? $this->group->label() : $this->group->label().' : '.$this->label;
    }
}
