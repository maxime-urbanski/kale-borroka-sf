<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Article;
use App\Entity\Book;
use App\Entity\Release;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
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
