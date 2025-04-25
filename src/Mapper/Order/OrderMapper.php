<?php

declare(strict_types=1);

namespace App\Mapper\Order;

use App\Data\OrderDeliveryDto;
use App\Entity\Order;
use App\Entity\User;

final readonly class OrderMapper
{
    public function mapDataToOrderResource(User $buyer, OrderDeliveryDto $orderDeliveryDto, int $totalPrice = 0): Order
    {
        $order = new Order();
        $reference = 'kbr-'.uniqid('', true);
        $order->setReference($reference);
        $order->setBuyer($buyer);
        $order->setCreatedAt(new \DateTimeImmutable('now'));
        $order->setStatus('PROCESS');
        $order->setAddress($orderDeliveryDto->deliveryAddress);
        $order->setDelivery($orderDeliveryDto->transporter);
        $order->setPayment($orderDeliveryDto->paymentMethod);
        $order->setTotalPrice($totalPrice);

        return $order;
    }
}
