<?php

declare(strict_types=1);

namespace App\Controller\Admin\Trait;

use App\Entity\Image;
use App\Repository\ImageRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;

/**
 * For CRUD controllers whose form edits a collection of pictures ("Visuels" tab): a picture
 * removed from the form is deleted, file included, once nothing else shows it.
 * The controller must list ImageRepository in getSubscribedServices().
 */
trait RemovesOrphanImagesTrait
{
    /**
     * Call before flushing: picks up what the form removed from the collection.
     *
     * @param Collection<int, Image> $images
     *
     * @return Image[]
     */
    private function removedImages(Collection $images): array
    {
        return $images instanceof PersistentCollection ? $images->getDeleteDiff() : [];
    }

    /**
     * Call after flushing, once the removed pictures are unlinked.
     *
     * @param Image[] $removed
     */
    private function deleteOrphanImages(EntityManagerInterface $entityManager, array $removed): void
    {
        $imageRepository = $this->container->get(ImageRepository::class);

        foreach ($removed as $image) {
            if (!$imageRepository->isInUse($image)) {
                $entityManager->remove($image);
            }
        }

        $entityManager->flush();
    }
}
