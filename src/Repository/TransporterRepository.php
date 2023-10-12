<?php

namespace App\Repository;

use App\Entity\Transporter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transporter>
 *
 * @method Transporter|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transporter|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transporter[]    findAll()
 * @method Transporter[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransporterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transporter::class);
    }
}
