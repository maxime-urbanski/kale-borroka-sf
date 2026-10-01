<?php

declare(strict_types=1);

namespace App\Catalog;

use App\Entity\Artist;
use App\Entity\Label;
use App\Entity\Style;

/**
 * What one import has resolved so far. Created per call: CatalogImporter is a shared service
 * and keeps no state between imports.
 */
final class CatalogImportState
{
    /** @var array<string, Label> key in the file => label */
    public array $labels = [];

    /** @var array<string, Label> lowercased name => label */
    public array $labelsByName = [];

    /** @var array<string, Artist> key in the file => artist */
    public array $artists = [];

    /** @var array<string, Artist|null> lowercased name => artist, null when not in the database */
    public array $artistsByName = [];

    /** @var array<string, Style|null> lowercased name => style, null when not an official one */
    public array $styles = [];

    /** @var array<string, true> */
    private array $seen = [];

    /**
     * @param array<string, int> $prices default price in cents by ReleaseFormat value
     */
    public function __construct(
        public readonly array $prices,
    ) {
    }

    /**
     * Whether a value is met for the first time in the file.
     */
    public function firstTime(string $kind, string $value): bool
    {
        if (isset($this->seen[$kind.':'.$value])) {
            return false;
        }

        return $this->seen[$kind.':'.$value] = true;
    }
}
