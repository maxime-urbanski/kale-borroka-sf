<?php

declare(strict_types=1);

namespace App\MessageHandler\Command;

use App\Message\Command\GroupJoinCommand;
use App\Repository\GroupRepository;
use App\Repository\UserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

#[AsMessageHandler]
final readonly class GroupJoinHandler
{
    public function __construct(
        private GroupRepository $groupRepository,
        private UserRepository $userRepository,
    )
    {
    }

    public function __invoke(GroupJoinCommand $command): void
    {
        $group = $this->groupRepository->find($command->groupId);

        if (!$group) {
            throw new \Exception('Le groupe nexiste pas');
        }

        $user = $this->userRepository->find($command->userId);

        if (!$user) {
            throw new UserNotFoundException();
        }

        if (\in_array($group, $user->getGroupsIn()->toArray(), true)) {
            throw new \Exception('Vous êtes déjà membres du groupe');
        }

        $this->groupRepository->joinGroup($group, $user);
    }
}
