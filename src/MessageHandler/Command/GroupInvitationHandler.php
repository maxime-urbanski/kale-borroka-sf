<?php

namespace App\MessageHandler\Command;

use App\Entity\Invitation;
use App\Exception\GroupException;
use App\Message\Command\GroupInvitationCommand;
use App\Repository\GroupRepository;
use App\Repository\InvitationRepository;
use App\Repository\UserRepository;
use Exception;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

#[AsMessageHandler]
final readonly class GroupInvitationHandler
{
    public function __construct(
        private InvitationRepository $invitationRepository,
        private UserRepository       $userRepository,
        private GroupRepository      $groupRepository,
    )
    {
    }

    public function __invoke(GroupInvitationCommand $query): Invitation
    {
        $user = $this->userRepository->find($query->userId);
        $guest = $this->userRepository->find($query->guestId);
        $group = $this->groupRepository->find($query->groupId);

        if (!$user) {
            throw new UserNotFoundException();
        }

        if (!$guest) {
            throw new UserNotFoundException();
        }

        if (!$group) {
            throw new GroupException($group->getId());
        }


        if (
            (\in_array($group, $user->getGroupsIn()->toArray(), true)) ||
            (\in_array($group, $guest->getGroupsIn()->toArray(), true))

        ) {
            throw new Exception('Déjà membres du groupe');
        }

        return $this->invitationRepository->createInvitation($user, $guest, $group);
    }
}
