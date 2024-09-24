<?php

namespace App\Message\Command;

class GroupInvitationCommand
{
    public int $userId;
    public int $guestId;
    public int $groupId;

    public function __construct(
        int $groupId,
        int $userId,
        int $guestId,
    ) {
        $this->userId = $userId;
        $this->guestId = $guestId;
        $this->groupId = $groupId;
    }
}
