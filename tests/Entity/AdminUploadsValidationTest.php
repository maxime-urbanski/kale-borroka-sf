<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\MediaObject;
use App\Entity\SocialNetwork;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Back-office content rendered on the shop's own origin must not be able to run scripts.
 */
class AdminUploadsValidationTest extends KernelTestCase
{
    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function urls(): iterable
    {
        yield 'https' => ['https://www.instagram.com/kaleborroka', true];
        yield 'http' => ['http://example.org', true];
        yield 'javascript' => ['javascript:alert(document.cookie)', false];
        yield 'data' => ['data:text/html,<script>alert(1)</script>', false];
    }

    #[DataProvider('urls')]
    public function testSocialNetworkUrlIsHttpOnly(string $url, bool $valid): void
    {
        $socialNetwork = (new SocialNetwork())->setName('Instagram')->setUrl($url);

        self::assertSame($valid, 0 === $this->validator()->validateProperty($socialNetwork, 'url')->count());
    }

    public function testMediaObjectRefusesSvg(): void
    {
        $svg = tempnam(sys_get_temp_dir(), 'svg');
        file_put_contents($svg, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        try {
            $media = (new MediaObject())->setFile(new File($svg));

            self::assertGreaterThan(0, $this->validator()->validateProperty($media, 'file')->count());
        } finally {
            unlink($svg);
        }
    }

    private function validator(): ValidatorInterface
    {
        return self::getContainer()->get(ValidatorInterface::class);
    }
}
