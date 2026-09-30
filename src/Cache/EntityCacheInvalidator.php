<?php

declare(strict_types=1);

namespace App\Cache;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Clears the cached fragments that depend on an entity class whenever Doctrine writes
 * one. Each inserted, updated or deleted entity invalidates:
 * - the tag of its class and of its parent classes (see EntityCacheTag), so
 *   `tags(['article'])` covers releases and books alike;
 * - the tags of the classes holding an association to it, because a fragment shows its
 *   children through them: replacing an Image changes no Article row, yet the card shows
 *   the article's cover. One level only: a fragment lists the entities it reads directly.
 * Collection changes (e.g. an album's styles) invalidate the owner's tags. Only the tags a
 * template uses (EntityCacheTag::FRAGMENT_TAGS) are written to the pool.
 *
 * Tags are collected in onFlush and invalidated in postFlush, once flush() has committed.
 * Inside an outer transaction (command bus, wrapInTransaction) the commit comes later: a
 * concurrent request could store the old data again in between, so the tags are
 * invalidated once more when the request or command terminates.
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

    /** @var array<string, true> */
    private array $tagsAfterCommit = [];

    /**
     * Classes holding an association, by target class. Built from the mapping, which does
     * not change between requests.
     *
     * @var array<class-string, list<class-string>>|null
     */
    private ?array $referrers = null;

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
            foreach ($this->tagsOf($entityManager, $entity::class) as $tag) {
                $this->pendingTags[$tag] = true;
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $tags = array_intersect(array_keys($this->pendingTags), EntityCacheTag::FRAGMENT_TAGS);
        $this->pendingTags = [];

        if ([] === $tags) {
            return;
        }

        $this->cache->invalidateTags($tags);

        if ($args->getObjectManager()->getConnection()->isTransactionActive()) {
            $this->tagsAfterCommit += array_fill_keys($tags, true);
        }
    }

    #[AsEventListener(event: KernelEvents::TERMINATE)]
    #[AsEventListener(event: ConsoleEvents::TERMINATE)]
    public function onTerminate(): void
    {
        if ([] === $this->tagsAfterCommit) {
            return;
        }

        $tags = array_keys($this->tagsAfterCommit);
        $this->tagsAfterCommit = [];

        $this->cache->invalidateTags($tags);
    }

    public function reset(): void
    {
        $this->pendingTags = [];
        $this->tagsAfterCommit = [];
    }

    /**
     * @param class-string $class
     *
     * @return list<string>
     */
    private function tagsOf(EntityManagerInterface $entityManager, string $class): array
    {
        $metadata = $entityManager->getClassMetadata($class);
        $classes = [$metadata->getName(), ...$metadata->parentClasses];

        foreach ([...$classes] as $displayed) {
            foreach ($this->referrers($entityManager)[$displayed] ?? [] as $referrer) {
                $classes = [...$classes, $referrer, ...$entityManager->getClassMetadata($referrer)->parentClasses];
            }
        }

        return array_values(array_unique(array_map(EntityCacheTag::forClass(...), $classes)));
    }

    /**
     * @return array<class-string, list<class-string>>
     */
    private function referrers(EntityManagerInterface $entityManager): array
    {
        if (null !== $this->referrers) {
            return $this->referrers;
        }

        $referrers = [];
        foreach ($entityManager->getMetadataFactory()->getAllMetadata() as $metadata) {
            foreach ($metadata->getAssociationNames() as $association) {
                $referrers[$metadata->getAssociationTargetClass($association)][] = $metadata->getName();
            }
        }

        return $this->referrers = $referrers;
    }
}
