<?php

declare(strict_types=1);

namespace App\Tests\Controller\Security;

use App\Tests\ServiceTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Passwords cannot be guessed without limit: after five failures for an account from one
 * address, even the right password is refused for a while.
 */
class LoginThrottlingTest extends WebTestCase
{
    use ServiceTrait;

    /** In both French texts of Symfony's TooManyLoginAttemptsAuthenticationException. */
    private const string THROTTLED = 'tentatives de connexion';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
        // The limiter state is in a cache pool that outlives the test.
        self::service(\Symfony\Component\Cache\Adapter\AdapterInterface::class, 'cache.rate_limiter')->clear();
    }

    public function testTheRightPasswordIsRefusedAfterFiveFailures(): void
    {
        for ($attempt = 1; $attempt <= 5; ++$attempt) {
            $this->logIn('test@test.fr', 'wrong password '.$attempt);
        }

        $this->logIn('test@test.fr', 'password123');

        self::assertSelectorTextContains('.alert-danger', self::THROTTLED);
        self::assertFalse($this->isLoggedIn(), 'still throttled');
    }

    /**
     * E-mails are case-insensitive and trimmed: neither must reset the count.
     */
    public function testChangingTheCaseOrSpacesOfTheEmailDoesNotHelp(): void
    {
        foreach (['TEST@test.fr', ' test@test.fr', 'test@test.fr ', 'Test@Test.fr', '  test@TEST.fr  '] as $email) {
            $this->logIn($email, 'wrong password');
        }

        $this->logIn('test@test.fr', 'password123');

        self::assertSelectorTextContains('.alert-danger', self::THROTTLED);
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
        // A failure redirects back to the login form, which shows the error.
        if ($this->client->getResponse()->isRedirect('/login')) {
            $this->client->followRedirect();
        }
    }

    private function isLoggedIn(): bool
    {
        $this->client->request('GET', '/mon-compte');

        return $this->client->getResponse()->isSuccessful();
    }
}
