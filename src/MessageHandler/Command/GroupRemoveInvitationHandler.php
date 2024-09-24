<?php

namespace App\MessageHandler\Command;

use App\Message\Command\GroupRemoveInvitationCommand;
use App\Repository\InvitationRepository;
use App\Repository\UserRepository;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class GroupRemoveInvitationHandler
{
    public function __construct(
        private InvitationRepository $invitationRepository,
        private UserRepository       $userRepository,
    )
    {
    }

    public function __invoke(GroupRemoveInvitationCommand $command): void
    {
        $invitation = $this->invitationRepository->find($command->invitationId);
        $user = $this->userRepository->find($command->userId);

        if (!$invitation) {
            throw new Exception("Invitation not found");
        }

        if (!$user) {
            throw new Exception("User not found");
        }

        if ($invitation->getGuest() !== $user) {
            throw new Exception('Vous devez être invité pour supprimer ce groupe');
        }

        $this->invitationRepository->delete($invitation);
    }
}
