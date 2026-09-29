<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Merch;
use App\Entity\MerchVariant;
use App\Enum\MerchSize;
use PHPUnit\Framework\TestCase;

class MerchVariantTest extends TestCase
{
    public function testNameFollowsTheDesignSizeAndColour(): void
    {
        $merch = (new Merch())->setName('T-shirt Brixton Cats');
        $variant = (new MerchVariant())->setSize(MerchSize::M)->setColor('noir');
        $merch->addVariant($variant);

        self::assertSame('T-shirt Brixton Cats - M noir', $variant->getName());

        $merch->setName('T-shirt Quartier Maudit');
        self::assertSame('T-shirt Quartier Maudit - M noir', $variant->getName());
    }

    public function testCoverFallsBackToTheDesignPictures(): void
    {
        $merch = new Merch();
        $variant = new MerchVariant();
        $merch->addVariant($variant);

        self::assertNull($variant->getCoverImage());
    }
}
