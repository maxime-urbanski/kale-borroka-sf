<?php

declare(strict_types=1);

namespace App\Tests\Order;

use App\Enum\OrderStatus;
use App\Enum\OrderTransition;
use App\Enum\PaymentStatus;
use App\Order\Command\ApplyOrderTransition;
use App\Order\Exception\InsufficientStockException;
use App\Order\Exception\InvalidOrderException;
use App\Repository\AddressRepository;
use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\Exception\NotEnabledTransitionException;

class OrderLifecycleTest extends KernelTestCase
{
    use OrderTestTrait;

    private const string CUSTOMER = 'test@test.fr';
    private const string OTHER_CUSTOMER = 'maxiloud@gmail.com';

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

    public function testPlacingAnOrderRecordsTheLinesWithoutTakingStock(): void
    {
        [$first, $second] = $this->releasesWithStock(5, 1);

        // 3 of the second are asked for but only 1 is left: the line is clamped.
        $order = $this->placeOrder($this->user(self::CUSTOMER), [$first->getId() => 2, $second->getId() => 3]);

        self::assertSame(OrderStatus::PENDING, $order->getStatus());
        self::assertSame(PaymentStatus::AWAITING, $order->getPaymentStatus());
        self::assertSame(3000, $order->getTotalPrice());
        self::assertSame([2, 1], $order->getOrderDetails()->map(fn ($line) => $line->getQuantity())->getValues());

        $line = $order->getOrderDetails()->first();
        self::assertSame($first->getName(), $line->getProductName());
        self::assertSame($first->getSku(), $line->getSku());
        self::assertSame(1000, $line->getUnitPrice());

        self::assertSame(5, $this->stockOf($first));
        self::assertSame(1, $this->stockOf($second));
    }

    public function testUnpublishedArticlesAreLeftOut(): void
    {
        [$release] = $this->releasesWithStock(5);
        $draft = self::getContainer()->get(ArticleRepository::class)->findOneBy(['published' => false]);
        self::assertNotNull($draft);

        $order = $this->placeOrder($this->user(self::CUSTOMER), [$release->getId() => 1, $draft->getId() => 1]);

        self::assertCount(1, $order->getOrderDetails());
    }

    public function testACartWithNothingAvailableIsRefused(): void
    {
        [$release] = $this->releasesWithStock(0);

        $this->expectException(InvalidOrderException::class);
        $this->placeOrder($this->user(self::CUSTOMER), [$release->getId() => 1]);
    }

    public function testAnotherCustomersAddressIsRefused(): void
    {
        [$release] = $this->releasesWithStock(5);
        $otherAddress = self::getContainer()->get(AddressRepository::class)
            ->findOneBy(['users' => $this->user(self::OTHER_CUSTOMER)]);

        $this->expectException(InvalidOrderException::class);
        $this->placeOrder($this->user(self::CUSTOMER), [$release->getId() => 1], (int) $otherAddress?->getId());
    }

    public function testTheOrderKeepsTheAddressAsItWasAtCheckout(): void
    {
        [$release] = $this->releasesWithStock(5);
        $order = $this->placeOrder($this->user(self::CUSTOMER), [$release->getId() => 1]);
        $address = $order->getAddress();
        self::assertNotNull($address);
        $snapshot = (string) $address;
        self::assertSame($snapshot, $order->getShippingAddress());

        $address->setCity('Ailleurs');
        $this->entityManager()->flush();
        $this->entityManager()->refresh($order);
        self::assertSame($snapshot, $order->getShippingAddress());

        // Deleting an address used by an order no longer fails on the foreign key
        // (DeleteUserAddressController unsets the default address first, as here).
        $buyer = $this->user(self::CUSTOMER);
        if ($buyer->getDefaultAddress() === $address) {
            $buyer->setDefaultAddress(null);
        }
        $this->entityManager()->remove($address);
        $this->entityManager()->flush();
        $this->entityManager()->refresh($order);
        self::assertNull($order->getAddress());
        self::assertSame($snapshot, $order->getShippingAddress());
    }

    public function testPayingTakesTheItemsOutOfStock(): void
    {
        [$first, $second] = $this->releasesWithStock(5, 2);
        $order = $this->placeOrder($this->user(self::CUSTOMER), [$first->getId() => 2, $second->getId() => 2]);

        $this->bus()->dispatch(new ApplyOrderTransition((int) $order->getId(), OrderTransition::PAY));

        self::assertSame(OrderStatus::PAID, $order->getStatus());
        self::assertSame(PaymentStatus::PAID, $order->getPaymentStatus());
        self::assertNotNull($order->getPaidAt());
        self::assertSame(3, $this->stockOf($first));
        self::assertSame(0, $this->stockOf($second));
    }

    /**
     * The last copy was sold in the meantime: nothing is taken, not even the lines that
     * were still available, and the order stays pending.
     */
    public function testPayingWithoutEnoughStockChangesNothing(): void
    {
        [$first, $second] = $this->releasesWithStock(5, 1);
        $order = $this->placeOrder($this->user(self::CUSTOMER), [$first->getId() => 2, $second->getId() => 1]);
        $this->entityManager()->getConnection()->executeStatement('UPDATE article SET stock = 0 WHERE id = ?', [$second->getId()]);

        try {
            $this->bus()->dispatch(new ApplyOrderTransition((int) $order->getId(), OrderTransition::PAY));
            self::fail('paying should have failed');
        } catch (InsufficientStockException $exception) {
            self::assertSame($second->getId(), $exception->article->getId());
        }

        self::assertSame(5, $this->stockOf($first));
        self::assertSame(0, $this->stockOf($second));
        $this->entityManager()->refresh($order);
        self::assertSame(OrderStatus::PENDING, $order->getStatus());
        self::assertSame(PaymentStatus::AWAITING, $order->getPaymentStatus());
    }

    public function testCancellingAPaidOrderPutsTheItemsBack(): void
    {
        [$release] = $this->releasesWithStock(5);
        $order = $this->placeOrder($this->user(self::CUSTOMER), [$release->getId() => 2]);
        $this->bus()->dispatch(new ApplyOrderTransition((int) $order->getId(), OrderTransition::PAY));

        $this->bus()->dispatch(new ApplyOrderTransition((int) $order->getId(), OrderTransition::CANCEL));

        self::assertSame(OrderStatus::CANCELLED, $order->getStatus());
        self::assertSame(PaymentStatus::REFUNDED, $order->getPaymentStatus());
        self::assertSame(5, $this->stockOf($release));
    }

    public function testCancellingAPendingOrderLeavesTheStockAlone(): void
    {
        [$release] = $this->releasesWithStock(5);
        $order = $this->placeOrder($this->user(self::CUSTOMER), [$release->getId() => 2]);

        $this->bus()->dispatch(new ApplyOrderTransition((int) $order->getId(), OrderTransition::CANCEL));

        self::assertSame(OrderStatus::CANCELLED, $order->getStatus());
        self::assertSame(PaymentStatus::AWAITING, $order->getPaymentStatus());
        self::assertSame(5, $this->stockOf($release));
    }

    public function testAnUnpaidOrderCannotBeShipped(): void
    {
        [$release] = $this->releasesWithStock(5);
        $order = $this->placeOrder($this->user(self::CUSTOMER), [$release->getId() => 1]);

        $this->expectException(NotEnabledTransitionException::class);
        $this->bus()->dispatch(new ApplyOrderTransition((int) $order->getId(), OrderTransition::SHIP));
    }

    public function testFullLifecycle(): void
    {
        [$release] = $this->releasesWithStock(5);
        $order = $this->placeOrder($this->user(self::CUSTOMER), [$release->getId() => 1]);

        foreach ([OrderTransition::PAY, OrderTransition::PREPARE, OrderTransition::SHIP, OrderTransition::DELIVER, OrderTransition::REFUND] as $transition) {
            $this->bus()->dispatch(new ApplyOrderTransition((int) $order->getId(), $transition));
        }

        self::assertSame(OrderStatus::REFUNDED, $order->getStatus());
        self::assertSame(PaymentStatus::REFUNDED, $order->getPaymentStatus());
        // Shipped items may or may not come back: a refund does not restock.
        self::assertSame(4, $this->stockOf($release));
    }
}
