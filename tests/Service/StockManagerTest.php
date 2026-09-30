<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Order\Exception\InsufficientStockException;
use App\Service\StockManagerInterface;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class StockManagerTest extends KernelTestCase
{
    use OrderTestTrait;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->beginIsolation();
    }

    protected function tearDown(): void
    {
        $this->endIsolation();
        parent::tearDown();
    }

    public function testTakeAndPutBack(): void
    {
        [$release] = $this->releasesWithStock(3);
        $stockManager = self::service(StockManagerInterface::class);

        $stockManager->take($release, 3);
        self::assertSame(0, $this->stockOf($release));
        self::assertSame(0, $release->getStock(), 'the entity is refreshed');

        $stockManager->putBack($release, 2);
        self::assertSame(2, $this->stockOf($release));
    }

    public function testStockNeverGoesBelowZero(): void
    {
        [$release] = $this->releasesWithStock(1);

        $this->expectException(InsufficientStockException::class);

        try {
            self::service(StockManagerInterface::class)->take($release, 2);
        } finally {
            self::assertSame(1, $this->stockOf($release));
        }
    }
}
