<?php

declare(strict_types=1);

namespace App\Tests\Controller\Security;

use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ContentSecurityPolicyTest extends WebTestCase
{
    use OrderTestTrait;

    private ?KernelBrowser $client = null;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
        $this->beginIsolation();
    }

    protected function tearDown(): void
    {
        $this->endIsolation();
        parent::tearDown();
    }

    public function testShopPagesOnlyRunOurOwnScripts(): void
    {
        $this->client->request('GET', '/');

        $policy = (string) $this->client->getResponse()->headers->get('Content-Security-Policy');
        self::assertStringContainsString("script-src 'self'", $policy);
        self::assertStringNotContainsString("'unsafe-inline'", explode(';', explode('script-src', $policy)[1])[0]);
        self::assertStringContainsString("object-src 'none'", $policy);
        self::assertStringContainsString("frame-ancestors 'self'", $policy);
    }

    public function testInlineScriptsOfTheBackOfficeCarryTheNonce(): void
    {
        $this->client->loginUser($this->user('maxiloud@gmail.com'));
        $crawler = $this->client->request('GET', '/admin');
        self::assertResponseIsSuccessful();

        preg_match("/'nonce-([^']+)'/", (string) $this->client->getResponse()->headers->get('Content-Security-Policy'), $match);
        self::assertNotEmpty($match[1] ?? null, 'EasyAdmin asked for a nonce');
        self::assertGreaterThan(0, $crawler->filter(\sprintf('script[nonce="%s"]', $match[1]))->count());
    }

    public function testBackOfficeSignOutLinkWorks(): void
    {
        $this->client->loginUser($this->user('maxiloud@gmail.com'));
        $crawler = $this->client->request('GET', '/admin');

        $link = $crawler->filter('a[href^="/logout?"]');
        self::assertGreaterThan(0, $link->count(), "EasyAdmin's user menu");
        $this->client->request('GET', (string) $link->first()->attr('href'));
        $this->client->request('GET', '/admin');
        self::assertResponseRedirects('/login');
    }

    public function testTheStyleNonceShownByTheBackOfficeDoesNotUnlockScripts(): void
    {
        $this->client->loginUser($this->user('maxiloud@gmail.com'));
        $crawler = $this->client->request('GET', '/admin');

        $styleNonce = (string) $crawler->filter('meta[name="csp-nonce"]')->attr('content');
        self::assertNotSame('', $styleNonce, 'EasyAdmin prints its style nonce');
        self::assertStringNotContainsString($styleNonce, (string) $this->client->getResponse()->headers->get('Content-Security-Policy'));
    }

    public function testNotSentOnFiles(): void
    {
        $this->client->loginUser($this->user('maxiloud@gmail.com'));
        $this->client->request('GET', '/admin/finances/export');

        self::assertNull($this->client->getResponse()->headers->get('Content-Security-Policy'));
    }

    public function testLogoutNeedsTheToken(): void
    {
        $this->client->loginUser($this->user('test@test.fr'));

        // Without a valid token, neither a link nor a forged form logs out: back to the
        // account page with a flash, not the firewall's bare 403.
        $this->client->request('GET', '/logout');
        self::assertResponseRedirects('/mon-compte');
        $this->client->request('POST', '/logout', ['_token' => 'forged']);
        self::assertResponseRedirects('/mon-compte');
        $this->client->request('GET', '/mon-compte');
        self::assertResponseIsSuccessful('still logged in');

        $crawler = $this->client->request('GET', '/mon-compte');
        $this->client->submit($crawler->filter('form[action="/logout"]')->form());
        $this->client->request('GET', '/mon-compte');
        self::assertResponseRedirects('/login');
    }
}
