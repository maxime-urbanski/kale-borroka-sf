<?php

declare(strict_types=1);

namespace App\Order\Command;

use App\Entity\Order;
use App\Entity\OrderDetails;
use App\Order\Exception\InvalidOrderException;
use App\Repository\AddressRepository;
use App\Repository\ArticleRepository;
use App\Repository\PaymentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * The total is the lines only: shipping is worked out by hand afterwards and mailed with the
 * amount to pay (the checkout offers no delivery mode).
 *
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
        private PaymentRepository $paymentRepository,
        private ArticleRepository $articleRepository,
    ) {
    }

    public function __invoke(PlaceOrder $command): string
    {
        $buyer = $this->userRepository->find($command->buyerId)
            ?? throw new InvalidOrderException('Client inconnu.');

        if (!$buyer->isEmailVerified()) {
            throw new InvalidOrderException('Confirme ton adresse e-mail avant de commander.');
        }
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
            ->setShippingAddress((string) $address)
            ->setCustomerMessage($command->message)
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

        // No shipping at checkout: the label works it out by hand and mails it with the amount to pay.
        $order->setShippingPrice(0)->setTotalPrice($total);
        $this->entityManager->persist($order);

        return (string) $order->getReference();
    }
}
