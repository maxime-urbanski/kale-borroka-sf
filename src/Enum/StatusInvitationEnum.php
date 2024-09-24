<?php

namespace App\Enum;

enum StatusInvitationEnum: string
{
    case Pending = 'En attente';
    case Accepted = 'Acceptée';
    case Declined = 'Refusée';
    case Expired = 'Expirée';
    case Canceled = 'Annulée';
    case Revoked = 'Révoquée';
}
