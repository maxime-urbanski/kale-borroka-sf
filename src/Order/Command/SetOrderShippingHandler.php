<?php

declare(strict_types=1);

namespace App\Order\Command;

use App\Enum\OrderStatus;
use App\Order\Exception\InvalidOrderException;
use App\Repository\OrderRepository;
use App\Repository\TransporterRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Only while the order waits for its payment: once paid, the total is what was paid.
 * The total becomes the lines plus the shipping.
 */
#[AsMessageHandler(bus: 'command.bus')]
readonly class SetOrderShippingHandler
{
    public function __construct(
        private OrderRepository $orderRepository,
        private TransporterRepository $transporterRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(SetOrderShipping $command): void
    {
        $order = $this->orderRepository->find($command->orderId)
            ?? throw new InvalidOrderException('Commande introuvable.');
        // Locked and re-read inside the bus transaction: a payment in another tab waits, then
        // this sees it (ApplyOrderTransitionHandler takes the same lock).
        $this->entityManager->refresh($order, LockMode::PESSIMISTIC_WRITE);

        if (OrderStatus::PENDING !== $order->getStatus()) {
            throw new InvalidOrderException(\sprintf('Le port ne se change plus : la commande est « %s ».', mb_strtolower($order->getStatus()->label())));
        }
        if ($command->shippingPrice < 0) {
            throw new InvalidOrderException('Le port ne peut pas être négatif.');
        }

        $lines = 0;
        foreach ($order->getOrderDetails() as $line) {
            $lines += (int) $line->getPrice();
        }

        $order
            ->setDelivery($this->transporterRepository->find($command->transporterId)
                ?? throw new InvalidOrderException('Transporteur inconnu.'))
            ->setShippingPrice($command->shippingPrice)
            ->setTotalPrice($lines + $command->shippingPrice);
    }
}
