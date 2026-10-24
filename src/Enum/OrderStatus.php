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
     * Bootstrap icon of the status: its badge and its step on the order's timeline.
     */
    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'bi-hourglass-split',
            self::PAID => 'bi-check-lg',
            self::PREPARING => 'bi-box-seam',
            self::SHIPPED => 'bi-truck',
            self::DELIVERED => 'bi-house-check',
            self::CANCELLED => 'bi-x-lg',
            self::REFUNDED => 'bi-arrow-counterclockwise',
        };
    }

    /**
     * Statuses of the normal cycle, in order: the timeline of an order's page (OrderProgress).
     *
     * @return list<self>
     */
    public static function cycle(): array
    {
        return [self::PENDING, self::PAID, self::PREPARING, self::SHIPPED, self::DELIVERED];
    }

    /**
     * Still moving, as « Mes commandes » groups them: the rest is over (delivered, cancelled, refunded).
     *
     * @return list<self>
     */
    public static function ongoing(): array
    {
        return [self::PENDING, self::PAID, self::PREPARING, self::SHIPPED];
    }

    /**
     * @return list<self>
     */
    public static function finished(): array
    {
        return [self::DELIVERED, self::CANCELLED, self::REFUNDED];
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
