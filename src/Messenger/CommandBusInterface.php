<?php

declare(strict_types=1);

namespace App\Messenger;

interface CommandBusInterface
{
    /**
     * Handles the command synchronously and returns the handler's result. Exceptions thrown
     * by the handler are rethrown as is, not wrapped in HandlerFailedException.
     */
    public function dispatch(object $command): mixed;
}
