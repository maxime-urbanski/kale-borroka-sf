<?php

declare(strict_types=1);

namespace App\Catalog;

/**
 * What an import created, and what it left alone because it was already there.
 */
final class CatalogImportReport
{
    /** @var array<string, int> entity kind => number of rows created */
    private array $created = [];

    /** @var list<string> */
    private array $skipped = [];

    public function created(string $kind): void
    {
        $this->created[$kind] = ($this->created[$kind] ?? 0) + 1;
    }

    public function skipped(string $what): void
    {
        $this->skipped[] = $what;
    }

    /**
     * @return array<string, int>
     */
    public function getCreated(): array
    {
        return $this->created;
    }

    public function countCreated(string $kind): int
    {
        return $this->created[$kind] ?? 0;
    }

    /**
     * @return list<string>
     */
    public function getSkipped(): array
    {
        return $this->skipped;
    }
}
