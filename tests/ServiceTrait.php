<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Typed access to the test container: get() returns a bare object, so every call on a
 * service would be unknown to PHPStan.
 */
trait ServiceTrait
{
    abstract protected static function getContainer(): ContainerInterface;

    /**
     * @template T of object
     *
     * @param class-string<T> $type the service's class or interface
     * @param string|null     $id   the service id, when it is not the type itself
     *
     * @return T
     */
    protected static function service(string $type, ?string $id = null): object
    {
        $service = self::getContainer()->get($id ?? $type);
        \assert($service instanceof $type);

        return $service;
    }
}
