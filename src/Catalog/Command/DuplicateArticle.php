<?php

declare(strict_types=1);

namespace App\Catalog\Command;

/**
 * Copies an article into a new unpublished draft ("LP rouge" from "LP noir").
 * Handled by DuplicateArticleHandler, which returns the copy.
 */
final readonly class DuplicateArticle
{
    public function __construct(
        public int $articleId,
    ) {
    }
}
