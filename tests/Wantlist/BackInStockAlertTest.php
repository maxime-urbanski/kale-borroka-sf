<?php

declare(strict_types=1);

namespace App\Tests\Wantlist;

use App\Entity\Book;
use App\Repository\WishlistItemRepository;
use App\Wantlist\BackInStockAlert;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;

/**
 * An alert is a courtesy: whatever fails on its way must not break the stock change that
 * triggered it (a back-office save, a cancelled order).
 */
class BackInStockAlertTest extends TestCase
{
    public function testUnreadableSubscribersAreLoggedNotThrown(): void
    {
        $repository = $this->createStub(WishlistItemRepository::class);
        $repository->method('emailsWithAlertOn')->willThrowException(new \RuntimeException('database down'));
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');

        new BackInStockAlert($repository, $mailer, $logger)->send(new Book());
    }

    public function testAFailedSendIsLoggedAndTheOthersStillGo(): void
    {
        $repository = $this->createStub(WishlistItemRepository::class);
        $repository->method('emailsWithAlertOn')->willReturn(['a@example.org', 'b@example.org']);
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::exactly(2))->method('send')
            ->willReturnOnConsecutiveCalls(self::throwException(new \RuntimeException('transport down')), null);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');

        new BackInStockAlert($repository, $mailer, $logger)->send(new Book());
    }
}
