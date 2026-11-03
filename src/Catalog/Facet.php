<?php

declare(strict_types=1);

namespace App\Catalog;

/**
 * A filter group as the page shows it: its values, the first $visible of them out in the
 * open, the others behind « Voir les N autres ».
 */
final readonly class Facet
{
    /**
     * @param list<FacetOption> $options
     */
    public function __construct(
        public CatalogFacet $group,
        public array $options,
        public int $visible,
    ) {
    }

    public function selectedCount(): int
    {
        return \count(array_filter($this->options, static fn (FacetOption $option): bool => $option->selected));
    }

    /** @return list<FacetOption> */
    public function shown(): array
    {
        return \array_slice($this->options, 0, $this->visible);
    }

    /** @return list<FacetOption> */
    public function more(): array
    {
        return \array_slice($this->options, $this->visible);
    }
}
