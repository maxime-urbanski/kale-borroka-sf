<?php

declare(strict_types=1);

namespace App\Cache;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Clears the cached fragments that depend on an entity class whenever Doctrine writes
 * one: each inserted, updated or deleted entity invalidates the tag of its class and of
 * its parent classes (see EntityCacheTag), so `tags(['article'])` covers releases and
 * books alike. Collection changes (e.g. an album's styles) invalidate the owner's tags.
 *
 * Tags are collected in onFlush and invalidated in postFlush, once flush() has committed,
 * so a concurrent request cannot store the old data again in between. Inside an outer
 * transaction (wrapInTransaction) that window reopens until the commit; the pool lifetime
 * bounds how long such a stale fragment can live.
 *
 * Writes that bypass the unit of work (DBAL statements, DQL UPDATE/DELETE, such as
 * StockManager) are not seen: cached fragments must not show the columns they touch.
 */
#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
final class EntityCacheInvalidator implements ResetInterface
{
    /** @var array<string, true> */
    private array $pendingTags = [];

    public function __construct(
        #[Target('cache.fragments')]
        private readonly TagAwareCacheInterface $cache,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        $entities = [
            ...$unitOfWork->getScheduledEntityInsertions(),
            ...$unitOfWork->getScheduledEntityUpdates(),
            ...$unitOfWork->getScheduledEntityDeletions(),
        ];

        foreach ([...$unitOfWork->getScheduledCollectionUpdates(), ...$unitOfWork->getScheduledCollectionDeletions()] as $collection) {
            if (null !== $owner = $collection->getOwner()) {
                $entities[] = $owner;
            }
        }

        foreach ($entities as $entity) {
            foreach ($this->tagsOf($entityManager, $entity) as $tag) {
                $this->pendingTags[$tag] = true;
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ([] === $this->pendingTags) {
            return;
        }

        $tags = array_keys($this->pendingTags);
        $this->pendingTags = [];

        $this->cache->invalidateTags($tags);
    }

    public function reset(): void
    {
        $this->pendingTags = [];
    }

    /**
     * @return list<string>
     */
    private function tagsOf(EntityManagerInterface $entityManager, object $entity): array
    {
        $metadata = $entityManager->getClassMetadata($entity::class);

        return array_map(EntityCacheTag::forClass(...), [$metadata->getName(), ...$metadata->parentClasses]);
    }
}
