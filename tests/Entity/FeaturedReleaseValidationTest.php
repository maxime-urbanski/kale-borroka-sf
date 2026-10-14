<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\FeaturedRelease;
use App\Tests\ServiceTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FeaturedReleaseValidationTest extends KernelTestCase
{
    use ServiceTrait;

    /**
     * The listen link is rendered on the homepage: no script may run from it.
     *
     * @return iterable<string, array{string, bool}>
     */
    public static function urls(): iterable
    {
        yield 'https' => ['https://kaleborroka.bandcamp.com/album/hautsa', true];
        yield 'http' => ['http://example.org', true];
        yield 'javascript' => ['javascript:alert(document.cookie)', false];
        yield 'data' => ['data:text/html,<script>alert(1)</script>', false];
    }

    #[DataProvider('urls')]
    public function testTheListenLinkIsHttpOnly(string $url, bool $valid): void
    {
        $feature = (new FeaturedRelease())->setListenUrl($url);

        self::assertSame($valid, 0 === $this->validator()->validateProperty($feature, 'listenUrl')->count());
    }

    public function testTheDisplayEndsAfterItStarts(): void
    {
        $feature = (new FeaturedRelease())
            ->setDatePublished(new \DateTimeImmutable('2026-10-14'))
            ->setExpires(new \DateTimeImmutable('2026-10-13'));

        self::assertGreaterThan(0, $this->validator()->validateProperty($feature, 'expires')->count());

        $feature->setExpires(new \DateTimeImmutable('2026-10-15'));
        self::assertCount(0, $this->validator()->validateProperty($feature, 'expires'));
    }

    public function testAReleaseIsRequired(): void
    {
        self::assertGreaterThan(0, $this->validator()->validateProperty(new FeaturedRelease(), 'release')->count());
    }

    private function validator(): ValidatorInterface
    {
        return self::service(ValidatorInterface::class);
    }
}
