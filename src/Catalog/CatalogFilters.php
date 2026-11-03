<?php

declare(strict_types=1);

namespace App\Catalog;

use App\Entity\Artist;
use App\Entity\Label;
use App\Entity\Style;
use App\Enum\ItemCondition;
use App\Enum\SupportType;

/**
 * What the catalogue page shows: the section of the URL (/catalog/{support}), then the
 * filters of its query string (CatalogFilterType). Values of a group add up (OR), groups
 * narrow each other (AND).
 */
final class CatalogFilters
{
    /** The section of the path, null on « Tout ». */
    public ?SupportType $section = null;

    /** « Nos productions » (/production): the label's own productions only, always. */
    public bool $productions = false;

    /** @var list<SupportType> sections ticked on « Tout »; ignored on a section's page */
    public array $supports = [];

    /** @var list<Style> */
    public array $styles = [];

    /** @var list<Label> an album's labels, a fanzine's publisher (the collective) */
    public array $labels = [];

    /** @var list<Artist> */
    public array $artists = [];

    /** @var list<ItemCondition> */
    public array $conditions = [];

    /** The label's own productions (KBR#) only. */
    public bool $kbrProduction = false;

    public bool $hideSoldOut = false;

    public CatalogSort $sort = CatalogSort::NEW;

    /** Narrows the list of artists offered in the filter, not the results. */
    public ?string $artistSearch = null;

    public static function of(?SupportType $section, bool $productions = false): self
    {
        $filters = new self();
        $filters->section = $section;
        $filters->productions = $productions;

        return $filters;
    }

    /**
     * @return list<SupportType> the sections the results are taken from, none for all
     */
    public function sections(): array
    {
        return null !== $this->section ? [$this->section] : $this->supports;
    }
}
