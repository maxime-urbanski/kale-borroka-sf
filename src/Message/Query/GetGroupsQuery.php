<?php

namespace App\Message\Query;

use App\Entity\User;

class GetGroupsQuery
{
    public ?User $user;

    public function __construct(?User $user)
    {
        $this->user = $user;
    }
}
