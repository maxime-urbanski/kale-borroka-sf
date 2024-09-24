<?php

declare(strict_types=1);

namespace App\Message\Command;

class GroupLeaveCommand
{
    public string $groupId;
    public int $userId;

    public function __construct(string $groupId, int $userId)
    {
        $this->groupId = $groupId;
        $this->userId = $userId;
    }
}
