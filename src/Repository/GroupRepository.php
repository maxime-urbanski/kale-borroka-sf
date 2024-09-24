<?php

namespace App\Repository;

use App\Entity\Group;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Group>
 *
 * @method Group|null find($id, $lockMode = null, $lockVersion = null)
 * @method Group|null findOneBy(array $criteria, array $orderBy = null)
 * @method Group[]    findAll()
 * @method Group[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Group::class);
    }

    public function getGroupsByUser(array $userId): Query
    {
        $query = $this->createQueryBuilder('g')
            ->leftJoin('g.members', 'members')
            ->where('members IN (:userId)')
            ->setParameter('userId', $userId);

        return $query->getQuery();
    }

    public function getUserGroupById(int $userId, int $groupId): Query
    {
        $query = $this->createQueryBuilder('g')
            ->leftJoin('g.members', 'm')
            ->where('g.id = :groupId')
            ->andWhere('m.id IN (:userId)')
            ->setParameter('groupId', $groupId)
            ->setParameter('userId', $userId);

        return $query->getQuery();
    }

    public function removeGroupMember(Group $group, User $user)
    {
        $group->removeMember($user);
        $this->getEntityManager()->flush();
    }

    public function joinGroup(Group $group, User $user): void
    {
        $group->addMember($user);
        $this->getEntityManager()->flush();
    }
}
