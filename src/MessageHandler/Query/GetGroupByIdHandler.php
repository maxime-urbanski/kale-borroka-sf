<?php

namespace App\MessageHandler\Query;

use App\Message\Query\GetGroupByIdQuery;
use App\Repository\GroupRepository;
use Doctrine\ORM\Query;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class GetGroupByIdHandler
{
    public function __construct(
        private GroupRepository $groupRepository
    ) {
    }

    public function __invoke(GetGroupByIdQuery $query): Query
    {
        return $this->groupRepository->getUserGroupById($query->user->getId(), $query->groupId);
    }
}
