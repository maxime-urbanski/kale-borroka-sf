<?php

declare(strict_types=1);

namespace App\Catalog;

/**
 * The catalogue file is malformed, or one of the entities it describes is invalid.
 * Nothing has been written when it is thrown.
 */
final class CatalogImportException extends \RuntimeException
{
    /**
     * @param list<string> $errors
     */
    public static function withErrors(array $errors): self
    {
        return new self(implode("\n", $errors));
    }
}
