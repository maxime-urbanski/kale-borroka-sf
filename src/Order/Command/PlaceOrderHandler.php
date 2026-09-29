<?php

declare(strict_types=1);

namespace App\Order\Command;

use App\Entity\Order;
use App\Entity\OrderDetails;
use App\Order\Exception\InvalidOrderException;
use App\Repository\AddressRepository;
use App\Repository\ArticleRepository;
use App\Repository\PaymentRepository;
use App\Repository\TransporterRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Stock is not taken here but when the order is paid (OrderWorkflowSubscriber): a pending
 * order only records what the customer asked for, clamped to what was available.
 */
#[AsMessageHandler(bus: 'command.bus')]
readonly class PlaceOrderHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private AddressRepository $addressRepository,
        private TransporterRepository $transporterRepository,
        private PaymentRepository $paymentRepository,
        private ArticleRepository $articleRepository,
    ) {
    }

    public function __invoke(PlaceOrder $command): string
    {
        $buyer = $this->userRepository->find($command->buyerId)
            ?? throw new InvalidOrderException('Client inconnu.');
        $address = $this->addressRepository->find($command->addressId);

        // The address comes from a form: never trust that it belongs to the buyer.
        if (null === $address || $address->getUsers() !== $buyer) {
            throw new InvalidOrderException('Adresse de livraison invalide.');
        }

        $order = (new Order())
            ->setReference('KBR-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(4))))
            ->setBuyer($buyer)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setAddress($address)
            ->setDelivery($this->transporterRepository->find($command->transporterId)
                ?? throw new InvalidOrderException('Mode de livraison invalide.'))
            ->setPayment($this->paymentRepository->find($command->paymentId)
                ?? throw new InvalidOrderException('Mode de paiement invalide.'));

        $total = 0;

        foreach ($command->lines as $articleId => $requested) {
            $article = $this->articleRepository->find($articleId);
            $quantity = min($requested, $article?->getStock() ?? 0);

            if (null === $article || !$article->isPublished() || $quantity <= 0) {
                continue;
            }

            $line = (new OrderDetails())
                ->setProduct($article)
                ->setProductName((string) $article->getName())
                ->setSku((string) $article->getSku())
                ->setUnitPrice((int) $article->getPrice())
                ->setQuantity($quantity)
                ->setPrice((int) $article->getPrice() * $quantity);

            $order->addOrderDetail($line);
            $total += $line->getPrice();
        }

        if ($order->getOrderDetails()->isEmpty()) {
            throw new InvalidOrderException('Aucun article de votre panier n\'est disponible.');
        }

        $order->setTotalPrice($total);
        $this->entityManager->persist($order);

        return (string) $order->getReference();
    }
}
