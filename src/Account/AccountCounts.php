<?php

declare(strict_types=1);

namespace App\Account;

use App\Entity\Address;
use App\Entity\Order;
use App\Entity\User;
use App\Entity\UserCollectionItems;
use App\Entity\WishlistItem;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Attribute\AsTwigFunction;

/**
 * Also `account_counts(user)` in Twig: the account menu shows them on every account page.
 */
readonly class AccountCounts implements AccountCountsInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[AsTwigFunction('account_counts')]
    public function of(User $user): AccountTotals
    {
        $counts = $this->entityManager->createQuery(\sprintf(
            'SELECT
                (SELECT COUNT(o.id) FROM %1$s o WHERE o.buyer = :user) AS orders,
                (SELECT COUNT(ci.added_at) FROM %2$s ci JOIN ci.collection c WHERE c.user = :user) AS collection,
                (SELECT COUNT(wi.added_at) FROM %3$s wi JOIN wi.wishlist w WHERE w.user = :user) AS wantlist,
                (SELECT COUNT(ws.added_at) FROM %3$s ws JOIN ws.wishlist ww JOIN ws.article wa
                    WHERE ww.user = :user AND wa.published = true AND wa.stock > 0) AS wantlistInStock,
                (SELECT COUNT(a.id) FROM %4$s a WHERE a.users = :user) AS addresses
            FROM %5$s u WHERE u = :user',
            Order::class,
            UserCollectionItems::class,
            WishlistItem::class,
            Address::class,
            User::class,
        ))
            ->setParameter('user', $user)
            ->getSingleResult();

        return new AccountTotals(
            (int) $counts['orders'],
            (int) $counts['collection'],
            (int) $counts['wantlist'],
            (int) $counts['wantlistInStock'],
            (int) $counts['addresses'],
        );
    }
}
