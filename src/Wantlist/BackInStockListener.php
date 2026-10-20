<?php

declare(strict_types=1);

namespace App\Wantlist;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Sends the wantlist alerts when a flush puts an article back on sale: stock from 0 to more,
 * or put online with stock (the back office). Seen in onFlush, sent in postFlush, once the
 * rows are written. Inside an outer transaction (command bus) the mails are queued in it:
 * a rollback drops them with the rest.
 *
 * Stock written through DBAL bypasses the unit of work: StockManager::putBack() calls
 * BackInStockAlert itself.
 */
#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
final class BackInStockListener implements ResetInterface
{
    /** @var array<int, Article> by id */
    private array $backOnSale = [];

    public function __construct(
        private readonly BackInStockAlert $alert,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $unitOfWork = $args->getObjectManager()->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof Article || null === $entity->getId()) {
                continue;
            }

            $changes = $unitOfWork->getEntityChangeSet($entity);
            if (!isset($changes['stock']) && !isset($changes['published'])) {
                continue;
            }

            $wasPublished = isset($changes['published']) ? (bool) $changes['published'][0] : $entity->isPublished();
            $stockBefore = isset($changes['stock']) ? (int) $changes['stock'][0] : (int) $entity->getStock();

            if (BackInStockAlert::isReturn($wasPublished, $stockBefore, $entity)) {
                $this->backOnSale[$entity->getId()] = $entity;
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $articles = $this->backOnSale;
        $this->backOnSale = [];

        foreach ($articles as $article) {
            $this->alert->send($article);
        }
    }

    public function reset(): void
    {
        $this->backOnSale = [];
    }
}
