<?php

namespace App\MessageHandler\Query;

use App\Message\Query\GetGroupsQuery;
use App\Repository\GroupRepository;
use Doctrine\ORM\Query;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class GetGroupsByHandler
{
    public function __construct(
        private GroupRepository $groupRepository
    ) {
    }

    public function __invoke(GetGroupsQuery $query): Query
    {
        return $this->groupRepository->getGroupsByUser([$query->user->getId()]);
    }
}
