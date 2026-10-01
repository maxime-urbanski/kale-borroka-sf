<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Album;
use App\Entity\Song;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Song>
 */
class SongRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Song::class);
    }

    public function save(Song $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * An album's tracks in order, with the artists they are credited to: one query, left
     * unexecuted for the template to run inside its cache block.
     */
    public function tracklistQuery(Album $album): Query
    {
        return $this->createQueryBuilder('song')
            ->addSelect('artist')
            ->innerJoin('song.albums', 'album')
            ->leftJoin('song.artist', 'artist')
            ->where('album = :album')
            ->setParameter('album', $album)
            ->orderBy('song.track', 'ASC')
            ->addOrderBy('artist.name', 'ASC')
            ->getQuery();
    }

    public function remove(Song $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
