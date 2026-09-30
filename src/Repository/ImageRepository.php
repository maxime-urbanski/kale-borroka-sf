<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Article;
use App\Entity\Image;
use App\Entity\Merch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Image>
 */
class ImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Image::class);
    }

    /**
     * Whether a picture is still shown anywhere: an album, an article or a merch design.
     * Articles and merch reference pictures one way only, hence the MEMBER OF queries.
     */
    public function isInUse(Image $image): bool
    {
        if (!$image->getAlbum()->isEmpty()) {
            return true;
        }

        foreach ([Article::class, Merch::class] as $owner) {
            $count = (int) $this->getEntityManager()->createQueryBuilder()
                ->select('COUNT(owner.id)')
                ->from($owner, 'owner')
                ->where(':image MEMBER OF owner.images')
                ->setParameter('image', $image)
                ->getQuery()
                ->getSingleScalarResult();

            if ($count > 0) {
                return true;
            }
        }

        return false;
    }
}
