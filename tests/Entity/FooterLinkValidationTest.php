<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\FooterColumn;
use App\Entity\FooterLink;
use App\Entity\Page;
use App\Entity\Support;
use App\Tests\ServiceTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FooterLinkValidationTest extends KernelTestCase
{
    use ServiceTrait;

    public function testALinkLeadsToOneTarget(): void
    {
        self::assertSame(['page'], $this->violations(new FooterLink()), 'nowhere');
        self::assertSame(['page'], $this->violations((new FooterLink())->setPage(new Page())->setSupport(new Support())), 'two targets');
        self::assertSame([], $this->violations((new FooterLink())->setSupport(new Support())));
    }

    public function testALinkToAnAddressNeedsATextAndASafeAddress(): void
    {
        self::assertSame(['name'], $this->violations((new FooterLink())->setUrl('https://kaleborroka.bandcamp.com')));
        self::assertSame(['url'], $this->violations((new FooterLink())->setName('Bandcamp')->setUrl('javascript:alert(1)')));
        self::assertSame(['url'], $this->violations((new FooterLink())->setName('Ailleurs')->setUrl('//evil.example')));
        self::assertSame([], $this->violations((new FooterLink())->setName('Distro')->setUrl('/catalog')));
    }

    public function testOnlyLinksAVisitorCanFollowAreShown(): void
    {
        self::assertTrue((new FooterLink())->setPage((new Page())->setPublished(true))->isShown());
        self::assertFalse((new FooterLink())->setPage(new Page())->isShown(), 'unpublished page');
        self::assertTrue((new FooterLink())->setSupport(new Support())->isShown());
        self::assertTrue((new FooterLink())->setName('Bandcamp')->setUrl('https://kaleborroka.bandcamp.com')->isShown());
        self::assertFalse((new FooterLink())->setName('Piège')->setUrl('javascript:alert(1)')->isShown(), 'written past the form');
        self::assertFalse((new FooterLink())->setUrl('https://kaleborroka.bandcamp.com')->isShown(), 'no text');
    }

    public function testAColumnListingTheSectionsHasNoLinkToOne(): void
    {
        $column = (new FooterColumn())->setName('Shop')->setSupportsListed(true)
            ->addLink((new FooterLink())->setSupport(new Support()));
        $validator = self::service(ValidatorInterface::class);

        self::assertSame(['links'], array_map(static fn ($violation): string => $violation->getPropertyPath(), iterator_to_array($validator->validate($column))));
        self::assertCount(0, $validator->validate($column->setSupportsListed(false)));
    }

    /**
     * @return list<string> the property paths in violation, sorted
     */
    private function violations(FooterLink $link): array
    {
        $paths = array_map(static fn ($violation): string => $violation->getPropertyPath(), iterator_to_array(self::service(ValidatorInterface::class)->validate($link)));
        sort($paths);

        return array_values(array_unique($paths));
    }
}
