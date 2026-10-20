<?php

declare(strict_types=1);

namespace App\Catalog;

use App\Entity\Article;
use App\Entity\Release;

/**
 * The plain-text names of a record as the wantlist and the collection show them: its artist,
 * its title (the album's name, the article's name when it has no album) and its reference
 * (KBR#025 for a label production, else the pressing's catalogue number).
 * Also `article_names(article)` in Twig.
 */
final class RecordNames
{
    /**
     * @return array{artist: string, title: string, ref: ?string}
     */
    public static function of(Article $article): array
    {
        $album = $article instanceof Release ? $article->getAlbum() : null;

        return [
            'artist' => $album?->getArtist()?->getName() ?? '',
            'title' => $album?->getName() ?? (string) $article->getName(),
            'ref' => $album?->isKbrProduction() && $album->getKbrProductionId()
                ? $album->getKbrProductionId()
                : ($article instanceof Release ? $article->getCatalogNumber() : null),
        ];
    }
}
