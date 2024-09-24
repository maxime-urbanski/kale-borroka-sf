<?php

namespace App\Exception;

class GroupException extends \DomainException
{
    public function __construct(int $id)
    {
        parent::__construct("Le groupe $id n'existe pas ou vous n'êtes pas authorisé à y accéder.");
    }
}
