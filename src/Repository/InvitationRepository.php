<?php

namespace App\Repository;

use App\Entity\Group;
use App\Entity\Invitation;
use App\Entity\User;
use App\Enum\StatusInvitationEnum;
use Composer\XdebugHandler\Status;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Invitation>
 *
 * @method Invitation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Invitation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Invitation[]    findAll()
 * @method Invitation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invitation::class);
    }

    public function save(Invitation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function decline(Invitation $entity, User $user): void
    {
        $entity->setStatus(StatusInvitationEnum::Declined->value);
        $this->getEntityManager()->flush();
    }

    public function delete (Invitation $entity): void
    {
        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();
    }

    public function createInvitation(
        User  $sentBy,
        User  $guest,
        Group $group
    ): Invitation
    {
        $invitation = new Invitation();
        $invitation->setSentBy($sentBy);
        $invitation->setGuest($guest);
        $invitation->setCommunity($group);

        $this->save($invitation, true);

        return $invitation;
    }
}
