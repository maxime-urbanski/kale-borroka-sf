<?php

declare(strict_types=1);

namespace App\Messenger;

use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

readonly class CommandBus implements CommandBusInterface
{
    /**
     * Autowired by argument name: `$commandBus` is the `command.bus` bus.
     */
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    public function dispatch(object $command): mixed
    {
        try {
            $envelope = $this->commandBus->dispatch($command);
        } catch (HandlerFailedException $exception) {
            throw current($exception->getWrappedExceptions()) ?: $exception;
        }

        return $envelope->last(HandledStamp::class)?->getResult();
    }
}
