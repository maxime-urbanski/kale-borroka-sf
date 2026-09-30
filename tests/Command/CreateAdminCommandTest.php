<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Repository\UserRepository;
use App\Repository\WishlistRepository;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class CreateAdminCommandTest extends KernelTestCase
{
    use OrderTestTrait;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->beginIsolation();
    }

    protected function tearDown(): void
    {
        $this->endIsolation();
        parent::tearDown();
    }

    public function testCreatesAnAdminWithTheGivenPassword(): void
    {
        $tester = $this->tester();
        $tester->setInputs(['un mot de passe solide', 'un mot de passe solide', 'Jane', 'Doe']);

        self::assertSame(Command::SUCCESS, $tester->execute(['email' => 'New.Admin@kbr.com']));

        $admin = self::service(UserRepository::class)->findOneByEmail('new.admin@kbr.com');
        self::assertNotNull($admin);
        self::assertContains('ROLE_ADMIN', $admin->getRoles());
        self::assertSame('Jane', $admin->getFirstname());
        self::assertNotNull(self::service(WishlistRepository::class)->findOneBy(['user' => $admin]));
    }

    public function testFailedConfirmationsCreateNothing(): void
    {
        $tester = $this->tester();
        $tester->setInputs(['un mot de passe solide', 'autre chose', 'trop court', 'trop court']);

        self::assertSame(Command::FAILURE, $tester->execute(['email' => 'new.admin@kbr.com']));
        self::assertNull(self::service(UserRepository::class)->findOneByEmail('new.admin@kbr.com'));
    }

    private function tester(): CommandTester
    {
        self::assertNotNull(self::$kernel);

        return new CommandTester((new Application(self::$kernel))->find('app:create-admin'));
    }
}
