<?php

namespace App\MessageBus;

interface QueryBusInterface
{
    public function query(object $query): mixed;
}
