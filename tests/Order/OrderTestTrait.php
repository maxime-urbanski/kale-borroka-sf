<?php

declare(strict_types=1);

namespace App\Tests\Order;

use App\Entity\Article;
use App\Entity\Order;
use App\Entity\User;
use App\Messenger\CommandBus;
use App\Messenger\CommandBusInterface;
use App\Order\Command\PlaceOrder;
use App\Repository\AddressRepository;
use App\Repository\PaymentRepository;
use App\Repository\ReleaseRepository;
use App\Repository\TransporterRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds orders from the fixtures. Every test runs inside a transaction rolled back in
 * tearDown, so orders and stock changes never leak into other tests.
 */
trait OrderTestTrait
{
    abstract protected static function getContainer(): ContainerInterface;

    protected function beginIsolation(): void
    {
        $this->entityManager()->getConnection()->beginTransaction();
    }

    protected function endIsolation(): void
    {
        $connection = $this->entityManager()->getConnection();

        while ($connection->isTransactionActive()) {
            $connection->rollBack();
        }
    }

    protected function entityManager(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * The alias is only injected into a controller, so the compiler removes it from the test container.
     */
    protected function bus(): CommandBusInterface
    {
        return new CommandBus(self::getContainer()->get('command.bus'));
    }

    protected function user(string $email): User
    {
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => $email]);
        self::assertInstanceOf(User::class, $user);

        return $user;
    }

    /**
     * Published releases with a known stock, flushed so the DBAL stock updates see it.
     *
     * @return Article[]
     */
    protected function releasesWithStock(int ...$stocks): array
    {
        $releases = self::getContainer()->get(ReleaseRepository::class)
            ->findBy(['published' => true], ['id' => 'ASC'], \count($stocks));
        self::assertCount(\count($stocks), $releases);

        foreach ($releases as $i => $release) {
            $release->setStock($stocks[$i])->setPrice(1000);
        }
        $this->entityManager()->flush();

        return $releases;
    }

    /**
     * @param array<int, int> $lines article id => quantity
     */
    protected function placeOrder(User $buyer, array $lines, ?int $addressId = null): Order
    {
        $container = self::getContainer();
        $address = $container->get(AddressRepository::class)->findOneBy(['users' => $buyer]);

        $reference = $this->bus()->dispatch(new PlaceOrder(
            buyerId: (int) $buyer->getId(),
            addressId: $addressId ?? (int) $address?->getId(),
            transporterId: (int) $container->get(TransporterRepository::class)->findOneBy([])?->getId(),
            paymentId: (int) $container->get(PaymentRepository::class)->findOneBy([])?->getId(),
            lines: $lines,
        ));

        $order = $this->entityManager()->getRepository(Order::class)->findOneBy(['reference' => $reference]);
        self::assertInstanceOf(Order::class, $order);

        return $order;
    }

    protected function stockOf(Article $article): int
    {
        return (int) $this->entityManager()->getConnection()
            ->fetchOne('SELECT stock FROM article WHERE id = ?', [$article->getId()]);
    }
}
