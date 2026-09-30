<?php

declare(strict_types=1);

namespace App\Tests\Cache;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The templates rendered inside a {% cache %} block are served to every visitor: anything
 * that depends on the current user or request would leak to the next one.
 */
class CachedTemplatesTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function cachedTemplates(): iterable
    {
        foreach ([
            'layout/_footer.html.twig',
            'layout/article/_section.html.twig',
            'layout/article/_article-grid.html.twig',
            'layout/article/_card.html.twig',
        ] as $template) {
            yield $template => [$template];
        }
    }

    #[DataProvider('cachedTemplates')]
    public function testCachedTemplateIsTheSameForEveryVisitor(string $template): void
    {
        $source = (string) file_get_contents(\dirname(__DIR__, 2).'/templates/'.$template);

        self::assertDoesNotMatchRegularExpression(
            '/\bapp\.(user|session|request|flashes|token)\b|csrf_token|is_granted|\burl\(|form\(|form_start|\|raw\b/',
            $source,
        );
    }
}
