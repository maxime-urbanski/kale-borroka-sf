<?php

declare(strict_types=1);

namespace App\Tests\Controller\Security;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Passwords cannot be guessed without limit: after five failures for an account from one
 * address, even the right password is refused for a while.
 */
class LoginThrottlingTest extends WebTestCase
{
    private ?KernelBrowser $client = null;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
        // The limiter state outlives the test (cache pool): a fresh address per run.
        $this->client->setServerParameter('REMOTE_ADDR', \sprintf('10.%d.%d.%d', random_int(0, 255), random_int(0, 255), random_int(1, 254)));
    }

    public function testTheRightPasswordIsRefusedAfterFiveFailures(): void
    {
        for ($attempt = 1; $attempt <= 5; ++$attempt) {
            $this->logIn('test@test.fr', 'wrong password '.$attempt);
        }

        $this->logIn('test@test.fr', 'password123');

        self::assertFalse($this->isLoggedIn(), 'still throttled');
    }

    /**
     * E-mails are case-insensitive: changing the case must not reset the count.
     */
    public function testChangingTheCaseOfTheEmailDoesNotHelp(): void
    {
        foreach (['TEST@test.fr', 'Test@Test.fr', 'test@TEST.fr', 'tEst@test.fr', 'test@test.FR'] as $email) {
            $this->logIn($email, 'wrong password');
        }

        $this->logIn('test@test.fr', 'password123');

        self::assertFalse($this->isLoggedIn(), 'still throttled');
    }

    public function testAFewFailuresDoNotLockTheAccount(): void
    {
        $this->logIn('test@test.fr', 'wrong password');
        $this->logIn('test@test.fr', 'wrong password');
        $this->logIn('test@test.fr', 'password123');

        self::assertTrue($this->isLoggedIn());
    }

    private function logIn(string $email, string $password): void
    {
        $crawler = $this->client->request('GET', '/login');
        $this->client->submit($crawler->filter('form[method="post"]')->last()->form([
            'email' => $email,
            'password' => $password,
        ]));
    }

    private function isLoggedIn(): bool
    {
        $this->client->request('GET', '/mon-compte');

        return $this->client->getResponse()->isSuccessful();
    }
}
