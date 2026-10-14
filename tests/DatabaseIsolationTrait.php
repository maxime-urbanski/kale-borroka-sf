<?php

declare(strict_types=1);

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Runs a test inside a transaction rolled back afterwards: call beginIsolation() in setUp()
 * and endIsolation() in tearDown(). A KernelBrowser needs disableReboot(), or each request
 * gets a new connection outside the transaction.
 */
trait DatabaseIsolationTrait
{
    use ServiceTrait;

    protected function beginIsolation(): void
    {
        self::service(EntityManagerInterface::class)->getConnection()->beginTransaction();
    }

    protected function endIsolation(): void
    {
        $connection = self::service(EntityManagerInterface::class)->getConnection();

        while ($connection->isTransactionActive()) {
            $connection->rollBack();
        }
    }
}
