<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Album;
use App\Repository\AlbumRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsDoctrineListener(event: Events::prePersist, priority: 100)]
final readonly class AlbumEventSubscriber
{
    public function __construct(
        private RequestStack $requestStack,
        private AlbumRepository $albumRepository,
    ) {
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function prePersist(PrePersistEventArgs $persistEventArgs): void
    {
        $albumEntity = $persistEventArgs->getObject();
        $method = $this->requestStack->getCurrentRequest()?->getMethod();

        if (
            !$albumEntity instanceof Album
            || !$albumEntity->isKbrProduction()
            || !\in_array($method, [Request::METHOD_POST, Request::METHOD_PATCH, Request::METHOD_PUT], true)
        ) {
            return;
        }

        $numberOfAlbumsProduced = $this->albumRepository->numberOfAlbumsProduced();
        $newKbrProductionId = \str_pad((string) ($numberOfAlbumsProduced + 1), 3, '0', STR_PAD_LEFT);
        $albumEntity->setKbrProductionId('KBR#'.$newKbrProductionId);
    }
}
