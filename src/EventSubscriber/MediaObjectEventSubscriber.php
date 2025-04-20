<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\MediaObject;
use App\Repository\MediaObjectRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Vich\UploaderBundle\Storage\StorageInterface;

#[AsDoctrineListener(event: Events::postPersist, priority: 100)]
final readonly class MediaObjectEventSubscriber
{
    public function __construct(
        private StorageInterface $storage,
        private RequestStack $requestStack,
        private MediaObjectRepository $mediaObjectRepository,
    ) {
    }

    public function postPersist(PostPersistEventArgs $eventArgs): void
    {
        $mediaObjectEntity = $eventArgs->getObject();
        $method = $this->requestStack->getCurrentRequest()?->getMethod();

        if (!$mediaObjectEntity instanceof MediaObject || !\in_array($method, [Request::METHOD_POST, Request::METHOD_PATCH, Request::METHOD_PUT], true)) {
            return;
        }

        $goodFilepath = $this->storage->resolveUri($mediaObjectEntity, 'file');
        $mediaObjectEntity->filename = $goodFilepath;

        $this->mediaObjectRepository->save($mediaObjectEntity, true);
    }
}
