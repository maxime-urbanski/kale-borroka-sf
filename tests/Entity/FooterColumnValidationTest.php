<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\FooterColumn;
use App\Entity\FooterLink;
use App\Entity\Support;
use App\Tests\ServiceTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FooterColumnValidationTest extends KernelTestCase
{
    use ServiceTrait;

    public function testAColumnListingTheSectionsHasNoLinkToOne(): void
    {
        $column = (new FooterColumn())->setName('Shop')->setSupportsListed(true)
            ->addLink((new FooterLink())->setSupport(new Support()));

        self::assertSame(['supportsListed'], $this->violations($column));
        self::assertSame([], $this->violations($column->setSupportsListed(false)));
    }

    /**
     * @return list<string> the property paths in violation
     */
    private function violations(FooterColumn $column): array
    {
        return array_values(array_map(
            static fn ($violation): string => $violation->getPropertyPath(),
            iterator_to_array(self::service(ValidatorInterface::class)->validate($column)),
        ));
    }
}
