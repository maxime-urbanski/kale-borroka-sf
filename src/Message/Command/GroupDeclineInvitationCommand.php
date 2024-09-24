<?php

namespace App\Message\Command;

class GroupDeclineInvitationCommand
{
    public int $invitationId;
    public int $userId;

    public function __construct(int $invitationId, int $userId)
    {
        $this->invitationId = $invitationId;
        $this->userId = $userId;
    }
}
