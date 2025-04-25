<?php

declare(strict_types=1);

namespace App\Mapper\Order;

use App\Entity\Order;
use App\Entity\OrderDetails;

final readonly class OrderDetailsMapper
{
    /**
     * @param array<string, mixed> $product
     */
    public function mapDataToOrderDetailsResource(array $product, Order $order): OrderDetails
    {
        $orderDetails = new OrderDetails();
        $orderDetails->setProduct($product['product']);

        if ($product['quantity'] > $product['quantityMaxAvailable']) {
            $orderDetails->setQuantity($product['quantityMaxAvailable']);
        } else {
            $orderDetails->setQuantity($product['quantity']);
        }

        $orderDetails->setPrice($product['product']->getPrice() * $orderDetails->getQuantity());

        $orderDetails->setOrders($order);

        return $orderDetails;
    }
}
