<?php

declare(strict_types=1);

namespace App\MessageHandler\Command;

use App\Exception\GroupException;
use App\Message\Command\GroupLeaveCommand;
use App\Repository\GroupRepository;
use App\Repository\UserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

#[AsMessageHandler]
class GroupLeaveHandler
{
    private GroupRepository $groupRepository;
    private UserRepository $userRepository;

    public function __construct(
        GroupRepository $groupRepository,
        UserRepository  $userRepository
    )
    {
        $this->groupRepository = $groupRepository;
        $this->userRepository = $userRepository;
    }

    public function __invoke(GroupLeaveCommand $command): void
    {
        $group = $this->groupRepository->find($command->groupId);

        if (!$group) {
            throw new GroupException((int)$command->groupId);
        }

        $user = $this->userRepository->find($command->userId);

        if (!$user) {
            throw new UserNotFoundException();
        }

        if (!(\in_array($group, $user->getGroupsIn()->toArray(), true))) {
            throw new \Exception('Vous ne pouvez pas quitter un groupe auquel vous nappartenais pas.');
        }

        $this->groupRepository->removeGroupMember($group, $user);
    }
}
