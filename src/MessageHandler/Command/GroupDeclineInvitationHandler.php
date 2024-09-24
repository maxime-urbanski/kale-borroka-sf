<?php

namespace App\MessageHandler\Command;

use App\Message\Command\GroupDeclineInvitationCommand;
use App\Repository\InvitationRepository;
use App\Repository\UserRepository;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class GroupDeclineInvitationHandler
{
    public function __construct(
        private InvitationRepository $invitationRepository,
        private UserRepository       $userRepository,
    )
    {
    }

    public function __invoke(GroupDeclineInvitationCommand $message): void
    {
        $invitation = $this->invitationRepository->find($message->invitationId);
        $user = $this->userRepository->find($message->userId);

        if (!$invitation) {
            throw new Exception("Invitation not found");
        }

        if (!$user) {
            throw new Exception("User not found");
        }

        if ($invitation->getGuest() !== $user) {
            throw new Exception('Oops! Guest not allowed.');
        }

        $this->invitationRepository->decline($invitation, $user);
    }
}
