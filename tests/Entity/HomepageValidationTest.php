<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Homepage;
use App\Tests\ServiceTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class HomepageValidationTest extends KernelTestCase
{
    use ServiceTrait;

    /**
     * The label block's link is rendered on the homepage: http(s), or a path of the shop.
     *
     * @return iterable<string, array{string, bool}>
     */
    public static function urls(): iterable
    {
        yield 'https' => ['https://kaleborroka.bandcamp.com', true];
        yield 'path' => ['/catalog/lp', true];
        yield 'javascript' => ['javascript:alert(document.cookie)', false];
        yield 'data' => ['data:text/html,<script>alert(1)</script>', false];
        yield 'protocol-relative' => ['//evil.example', false];
        yield 'backslash' => ['/\\evil.example', false];
        yield 'spaces' => ['/page/a b', false];
    }

    #[DataProvider('urls')]
    public function testTheLinkIsHttpOrAShopPath(string $url, bool $valid): void
    {
        $homepage = (new Homepage())->setManifestoUrl($url);

        self::assertSame($valid, 0 === self::service(ValidatorInterface::class)->validateProperty($homepage, 'manifestoUrl')->count());
    }

    public function testTheSloganNeedsALine(): void
    {
        $homepage = (new Homepage())->setManifestoHeadline(" \n ")->setManifestoHighlight(null);

        self::assertGreaterThan(0, self::service(ValidatorInterface::class)->validate($homepage)->count());

        // A hidden block may be left empty.
        self::assertCount(0, self::service(ValidatorInterface::class)->validate($homepage->setManifestoShown(false)));
    }
}
