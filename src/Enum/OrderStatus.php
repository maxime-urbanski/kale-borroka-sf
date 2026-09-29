<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Places of the `order` workflow (config/packages/workflow.yaml). Never set directly:
 * apply an OrderTransition through the workflow so stock and payment follow.
 */
enum OrderStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case PREPARING = 'preparing';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente de paiement',
            self::PAID => 'Payée',
            self::PREPARING => 'En préparation',
            self::SHIPPED => 'Expédiée',
            self::DELIVERED => 'Livrée',
            self::CANCELLED => 'Annulée',
            self::REFUNDED => 'Remboursée',
        };
    }

    /**
     * Whether the order's items have been taken out of stock.
     */
    public function holdsStock(): bool
    {
        return match ($this) {
            self::PAID, self::PREPARING, self::SHIPPED, self::DELIVERED, self::REFUNDED => true,
            self::PENDING, self::CANCELLED => false,
        };
    }
}
