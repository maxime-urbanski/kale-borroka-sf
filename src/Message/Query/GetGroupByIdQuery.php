<?php

namespace App\Message\Query;

use App\Entity\User;

class GetGroupByIdQuery
{
    public string $groupId;
    public ?User $user;

    public function __construct(User $user, string $groupId)
    {
        $this->groupId = $groupId;
        $this->user = $user;
    }
}
