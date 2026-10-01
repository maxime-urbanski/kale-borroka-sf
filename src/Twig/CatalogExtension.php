<?php

declare(strict_types=1);

namespace App\Twig;

use App\Catalog\TracklistLayout;
use App\Entity\Article;
use App\Entity\Book;
use App\Entity\Release;
use App\Form\Type\DurationType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * Lets templates handle any kind of Article without knowing its subclass:
 * `article_path(article)` for links, `article is release` before touching album fields.
 */
class CatalogExtension extends AbstractExtension
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('article_path', $this->articlePath(...)),
            // `tracklist_sides(tracks, release.format)`: the tracks grouped by side, see TracklistLayout.
            new TwigFunction('tracklist_sides', TracklistLayout::sides(...)),
        ];
    }

    public function getFilters(): array
    {
        return [
            // `track.duration|duration`: 3:42, or 1:02:05 past an hour, as typed in the back office.
            new TwigFilter('duration', DurationType::format(...)),
        ];
    }

    public function getTests(): array
    {
        return [
            new TwigTest('release', static fn (mixed $article): bool => $article instanceof Release),
            new TwigTest('book', static fn (mixed $article): bool => $article instanceof Book),
        ];
    }

    /**
     * Articles without a catalogue section (merch, for now) link to the catalogue home.
     */
    public function articlePath(Article $article): string
    {
        $supportType = $article->getSupportType();

        if (null === $supportType || null === $article->getSlug()) {
            return $this->urlGenerator->generate('app_catalog');
        }

        return $this->urlGenerator->generate('app_catalog_show', [
            'support' => $supportType->value,
            'slug' => $article->getSlug(),
        ]);
    }
}
