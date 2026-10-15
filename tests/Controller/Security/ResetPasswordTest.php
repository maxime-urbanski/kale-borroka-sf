<?php

declare(strict_types=1);

namespace App\Tests\Controller\Security;

use App\Repository\UserRepository;
use App\Tests\DatabaseIsolationTrait;
use App\Tests\ServiceTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Mot de passe oublié (maquette 09): request, e-mail, new password, logged in.
 */
class ResetPasswordTest extends WebTestCase
{
    use DatabaseIsolationTrait;
    use ServiceTrait;

    private const string NEW_PASSWORD = 'Kale-Borroka-2026!';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
        // The reset request is stored (and throttled) in the database: roll it back.
        $this->beginIsolation();
        // The per-address limiter state is in a cache pool that outlives the test.
        self::service(\Symfony\Component\Cache\Adapter\AdapterInterface::class, 'cache.rate_limiter')->clear();
    }

    protected function tearDown(): void
    {
        $this->endIsolation();
        parent::tearDown();
    }

    public function testTheMailIsInFrenchWithAnHtmlAndATextPart(): void
    {
        $this->requestReset('test@test.fr');

        self::assertQueuedEmailCount(1);
        $email = self::getMailerMessage();
        self::assertInstanceOf(Email::class, $email);
        self::assertEmailAddressContains($email, 'To', 'test@test.fr');
        self::assertSame('Réinitialise ton mot de passe', $email->getSubject());
        // No name: the account's names are typed by whoever registered, never verified.
        self::assertSame('test@test.fr', $email->getTo()[0]->toString());
        self::assertEmailHtmlBodyContains($email, 'Choisir un nouveau mot de passe');
        self::assertEmailHtmlBodyContains($email, '1 heure');
        self::assertEmailTextBodyContains($email, $this->resetLink());
        self::assertEmailTextBodyContains($email, '1 heure');
    }

    /**
     * The SMTP round-trip happens in the worker, not in the request: it no longer tells a known
     * address from an unknown one by the response time, and an SMTP outage is no 500.
     */
    public function testTheMailIsQueuedAndNotSentDuringTheRequest(): void
    {
        $this->requestReset('test@test.fr');

        self::assertQueuedEmailCount(1);
        $transport = self::service(InMemoryTransport::class, 'messenger.transport.async');
        $sent = $transport->getSent();
        self::assertCount(1, $sent);
        $queued = $sent[0]->getMessage();
        self::assertInstanceOf(SendEmailMessage::class, $queued);

        // Rendered in the request, whose host passed SYMFONY_TRUSTED_HOSTS: the worker has no
        // request, its links would point to router.default_uri, and it may run another version.
        $email = $queued->getMessage();
        self::assertInstanceOf(Email::class, $email);
        self::assertStringContainsString('http://localhost/reset-password/reset/', (string) $email->getTextBody());
        self::assertStringContainsString('http://localhost/reset-password/reset/', (string) $email->getHtmlBody());

        // The transport and the mailer events are reset with each request.
        $this->requestReset('nobody@example.org');
        self::assertQueuedEmailCount(0);
        self::assertCount(0, $transport->getSent(), 'an unknown address queues nothing');
    }

    public function testTheWholeJourneyEndsLoggedInWithTheNewPassword(): void
    {
        $this->requestReset('test@test.fr');
        self::assertResponseRedirects('/reset-password/check-email');
        // The mailer collector only knows the mails of the last request.
        $link = $this->resetLink();
        $this->client->followRedirect();
        self::assertSelectorTextContains('h1', 'Regarde ta boîte mail');
        self::assertSelectorTextContains('main', 'test@test.fr');
        self::assertSelectorTextContains('main', 'après 15 minutes');

        // The token leaves the URL for the session before the form shows.
        $this->client->request('GET', $link);
        self::assertResponseRedirects('/reset-password/reset');
        $crawler = $this->client->followRedirect();
        self::assertSelectorTextContains('h1', 'Nouveau mot de passe');

        $this->client->submit($crawler->filter('form[name="change_password_form"]')->form([
            'change_password_form[plainPassword][first]' => self::NEW_PASSWORD,
            'change_password_form[plainPassword][second]' => self::NEW_PASSWORD,
        ]));

        self::assertResponseRedirects('/mon-compte');
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.kb-toasts', 'Mot de passe modifié');

        $user = self::service(UserRepository::class)->findOneByEmail('test@test.fr');
        self::assertNotNull($user);
        self::assertTrue(self::service(UserPasswordHasherInterface::class)->isPasswordValid($user, self::NEW_PASSWORD));

        // Used once: the same link no longer opens the form.
        $this->client->request('GET', $link);
        $this->client->followRedirect();
        self::assertResponseRedirects('/reset-password');
    }

    public function testFromTheOrderTunnelTheResetGoesBackToIt(): void
    {
        // « Valider le panier » while logged out: the firewall remembers the tunnel.
        $this->client->request('GET', '/order/delivery');
        self::assertResponseRedirects('/login');

        $this->requestReset('test@test.fr');
        $this->client->request('GET', $this->resetLink());
        $crawler = $this->client->followRedirect();
        $this->client->submit($crawler->filter('form[name="change_password_form"]')->form([
            'change_password_form[plainPassword][first]' => self::NEW_PASSWORD,
            'change_password_form[plainPassword][second]' => self::NEW_PASSWORD,
        ]));

        self::assertResponseRedirects('http://localhost/order/delivery');
    }

    public function testAnUnknownAddressGetsTheSamePageAndNoMail(): void
    {
        $this->requestReset('nobody@example.org');

        self::assertResponseRedirects('/reset-password/check-email');
        self::assertQueuedEmailCount(0);
        $this->client->followRedirect();
        self::assertSelectorTextContains('h1', 'Regarde ta boîte mail');
        self::assertSelectorTextContains('main', 'nobody@example.org');
    }

    public function testTheNewPasswordFollowsTheRules(): void
    {
        $this->requestReset('test@test.fr');
        $this->client->request('GET', $this->resetLink());
        $crawler = $this->client->followRedirect();

        $this->client->submit($crawler->filter('form[name="change_password_form"]')->form([
            'change_password_form[plainPassword][first]' => 'kaleborroka',
            'change_password_form[plainPassword][second]' => 'kaleborroka',
        ]));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertSelectorTextContains('form[name="change_password_form"]', 'au moins 12 caractères');
    }

    public function testTheTwoFieldsMustMatch(): void
    {
        $this->requestReset('test@test.fr');
        $this->client->request('GET', $this->resetLink());
        $crawler = $this->client->followRedirect();

        $this->client->submit($crawler->filter('form[name="change_password_form"]')->form([
            'change_password_form[plainPassword][first]' => self::NEW_PASSWORD,
            'change_password_form[plainPassword][second]' => self::NEW_PASSWORD.'?',
        ]));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertSelectorTextContains('form[name="change_password_form"]', 'Les deux mots de passe ne correspondent pas.');
    }

    public function testAForgedTokenSendsBackToTheRequestForm(): void
    {
        $this->client->request('GET', '/reset-password/reset/'.str_repeat('a', 40));
        $this->client->followRedirect();

        self::assertResponseRedirects('/reset-password');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.toast-danger', 'Ce lien ne marche plus');
    }

    public function testOneAddressCannotAskForMailsWithoutLimit(): void
    {
        for ($attempt = 1; $attempt <= 5; ++$attempt) {
            $this->requestReset(\sprintf('nobody%d@example.org', $attempt));
            self::assertResponseRedirects('/reset-password/check-email');
        }

        $this->requestReset('test@test.fr');

        self::assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);
        self::assertSelectorTextContains('form[name="reset_password_request_form"]', 'Trop de demandes');
        self::assertQueuedEmailCount(0);
    }

    private function requestReset(string $email): void
    {
        $crawler = $this->client->request('GET', '/reset-password');
        $this->client->submit($crawler->filter('form[name="reset_password_request_form"]')->form([
            'reset_password_request_form[email]' => $email,
        ]));
    }

    /**
     * The link of the last mail, as a path.
     */
    private function resetLink(): string
    {
        $email = self::getMailerMessage();
        self::assertInstanceOf(Email::class, $email);
        self::assertSame(1, preg_match('#https?://[^/\s"]+(/reset-password/reset/[A-Za-z0-9]+)#', (string) $email->getTextBody(), $match));

        return $match[1];
    }
}
