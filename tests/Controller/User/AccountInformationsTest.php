<?php

declare(strict_types=1);

namespace App\Tests\Controller\User;

use App\Entity\User;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\Mime\Email;

/**
 * Informations (maquette 14): the names change at once, the e-mail only once the link sent to
 * the new address is opened.
 */
class AccountInformationsTest extends WebTestCase
{
    use OrderTestTrait;

    private KernelBrowser $client;

    /** test@test.fr's id: their e-mail changes along the tests. */
    private int $userId;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
        $this->beginIsolation();
        $user = $this->user('test@test.fr');
        $this->userId = (int) $user->getId();
        $this->client->loginUser($user);
        // The limiter state is in a cache pool that outlives the test.
        self::service(\Symfony\Component\Cache\Adapter\AdapterInterface::class, 'cache.rate_limiter')->clear();
    }

    protected function tearDown(): void
    {
        $this->endIsolation();
        parent::tearDown();
    }

    public function testThePageShowsTheIdentityBlock(): void
    {
        $this->client->request('GET', '/mon-compte/editer');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Informations');
        self::assertSelectorTextContains('.kb-account__head', 'Membre depuis');
        self::assertSelectorTextContains('.kb-settings__block h2', 'Identité');
        self::assertSelectorTextContains('form[name="user_information_form"]', 'on envoie un lien de confirmation à la nouvelle adresse');
    }

    public function testTheNamesAreSavedAtOnce(): void
    {
        $form = $this->form();
        $form['user_information_form[firstname]'] = 'Maialen';
        $form['user_information_form[lastname]'] = 'Etxeberria';
        $this->client->submit($form);

        self::assertResponseRedirects('/mon-compte/editer');
        self::assertQueuedEmailCount(0);
        $user = $this->reloadUser();
        self::assertSame('Maialen', $user->getFirstname());
        self::assertSame('test@test.fr', $user->getEmail());
    }

    public function testANewEmailIsOnlyTakenOnceConfirmed(): void
    {
        $link = $this->askForEmail('Nouveau@Test.fr');

        self::assertSame('test@test.fr', $this->reloadUser()->getEmail(), 'not before the link is opened');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.kb-settings__pending', 'nouveau@test.fr');

        $this->client->request('GET', $link);

        self::assertResponseRedirects('/mon-compte/editer');
        self::assertSame('nouveau@test.fr', $this->reloadUser()->getEmail());
        self::assertNull($this->reloadUser()->getPendingEmail());
        // Every other device signs in again (their token holds the old identifier); this one
        // gets a remember-me cookie for the new one.
        self::assertResponseHasCookie('REMEMBERME');
        // The old address hears of it: someone else may have changed it.
        self::assertQueuedEmailCount(1);
        $notice = self::getMailerMessage();
        self::assertInstanceOf(Email::class, $notice);
        self::assertSame('test@test.fr', $notice->getTo()[0]->getAddress());
        self::assertEmailTextBodyContains($notice, 'nouveau@test.fr');
    }

    public function testALinkWorksOnce(): void
    {
        $link = $this->askForEmail('nouveau@test.fr');
        $this->client->request('GET', $link);
        $this->askForEmail('troisieme@test.fr');

        // The first link again: it was bound to the address it replaced.
        $this->client->request('GET', $link);

        self::assertResponseRedirects('/mon-compte/editer');
        $this->client->followRedirect();
        self::assertSelectorTextContains('body', 'Ce lien ne marche plus');
        self::assertSame('nouveau@test.fr', $this->reloadUser()->getEmail());
    }

    public function testAConfirmedEmailSignsTheOtherDevicesOut(): void
    {
        $this->client->request('GET', '/mon-compte');
        $other = $this->sessionCookie();
        $this->client->getCookieJar()->clear();
        $this->client->loginUser($this->reloadUser());

        $this->client->request('GET', $this->askForEmail('nouveau@test.fr'));
        $this->client->request('GET', '/mon-compte/editer');
        self::assertResponseIsSuccessful('this device stays signed in');

        $this->client->getCookieJar()->set($other);
        $this->client->request('GET', '/mon-compte/editer');
        self::assertResponseRedirects('/login', null, 'the other device is signed out');
    }

    public function testANewRequestCancelsTheOlderLink(): void
    {
        $typo = $this->askForEmail('nouveua@test.fr');
        $this->askForEmail('nouveau@test.fr');

        $this->client->request('GET', $typo);

        self::assertSame('test@test.fr', $this->reloadUser()->getEmail());
        self::assertSame('nouveau@test.fr', $this->reloadUser()->getPendingEmail());
    }

    public function testChangingTheEmailAsksForThePassword(): void
    {
        foreach (['', 'mauvais'] as $password) {
            $form = $this->form();
            $form['user_information_form[email]'] = 'nouveau@test.fr';
            $form['user_information_form[current_password]'] = $password;
            $this->client->submit($form);

            self::assertResponseStatusCodeSame(422);
            self::assertSelectorExists('#user_information_form_current_password.is-invalid');
            self::assertQueuedEmailCount(0);
        }

        // Names only: no password needed.
        $form = $this->form();
        $form['user_information_form[firstname]'] = 'Maialen';
        $this->client->submit($form);
        self::assertResponseRedirects('/mon-compte/editer');
    }

    public function testRequestsAreLimited(): void
    {
        $this->askForEmail('un@test.fr');
        $this->askForEmail('deux@test.fr');
        $this->askForEmail('trois@test.fr');

        $form = $this->form();
        $form['user_information_form[email]'] = 'quatre@test.fr';
        $form['user_information_form[current_password]'] = 'password123';
        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form[name="user_information_form"]', 'Trop de demandes');
        self::assertQueuedEmailCount(0);
    }

    public function testAPasswordChangeCancelsAPendingEmail(): void
    {
        $link = $this->askForEmail('nouveau@test.fr');
        $form = $this->client->request('GET', '/mon-compte/editer-mot-de-passe')->filter('form[name="update_password_form"]')->form();
        $form['update_password_form[current_password]'] = 'password123';
        $form['update_password_form[new_password][first]'] = 'Kale-Borroka-2026!';
        $form['update_password_form[new_password][second]'] = 'Kale-Borroka-2026!';
        $this->client->submit($form);

        $this->client->request('GET', $link);

        self::assertSame('test@test.fr', $this->reloadUser()->getEmail());
    }

    public function testATamperedLinkChangesNothing(): void
    {
        $link = $this->askForEmail('nouveau@test.fr');

        $this->client->request('GET', str_replace('nouveau%40test.fr', 'pirate%40test.fr', $link));

        self::assertResponseRedirects('/mon-compte/editer');
        self::assertSame('test@test.fr', $this->reloadUser()->getEmail());
    }

    public function testAnotherAccountCannotUseTheLink(): void
    {
        $link = $this->askForEmail('nouveau@test.fr');
        $this->client->loginUser($this->user('client1@kaleborroka.test'));

        $this->client->request('GET', $link);

        self::assertSame('test@test.fr', $this->reloadUser()->getEmail());
        self::assertNotNull(self::service(\App\Repository\UserRepository::class)->findOneByEmail('client1@kaleborroka.test'));
    }

    public function testAnAddressTakenMeanwhileIsRefused(): void
    {
        $link = $this->askForEmail('nouveau@test.fr');
        $this->user('client1@kaleborroka.test')->setEmail('nouveau@test.fr');
        $this->entityManager()->flush();

        $this->client->request('GET', $link);

        self::assertSame('test@test.fr', $this->reloadUser()->getEmail());
    }

    public function testTheLinkNeedsALogin(): void
    {
        $link = $this->askForEmail('nouveau@test.fr');
        $this->client->getCookieJar()->clear();

        $this->client->request('GET', $link);

        self::assertResponseRedirects('/login');
        self::assertSame('test@test.fr', $this->reloadUser()->getEmail());
    }

    /**
     * Submits the new address and returns the link of the mail sent to it.
     */
    private function askForEmail(string $email): string
    {
        $form = $this->form();
        $form['user_information_form[email]'] = $email;
        $form['user_information_form[current_password]'] = 'password123';
        $this->client->submit($form);

        self::assertResponseRedirects('/mon-compte/editer');
        // The link to the new address, and a word to the current one, before anything changes.
        self::assertQueuedEmailCount(2);
        $mails = [];
        foreach (self::getMailerMessages() as $mail) {
            self::assertInstanceOf(Email::class, $mail);
            $mails[$mail->getTo()[0]->getAddress()] = $mail;
        }
        self::assertArrayHasKey(mb_strtolower($email), $mails);
        self::assertArrayHasKey((string) $this->reloadUser()->getEmail(), $mails);
        self::assertEmailTextBodyContains($mails[(string) $this->reloadUser()->getEmail()], mb_strtolower($email));
        self::assertSame(1, preg_match('~https?://\S+/mon-compte/confirmer-email\S+~', (string) $mails[mb_strtolower($email)]->getTextBody(), $match));

        return $match[0];
    }

    private function sessionCookie(): Cookie
    {
        foreach ($this->client->getCookieJar()->all() as $cookie) {
            if (str_starts_with($cookie->getName(), 'MOCKSESSID') || 'PHPSESSID' === $cookie->getName()) {
                return $cookie;
            }
        }

        self::fail('no session cookie');
    }

    private function form(): Form
    {
        return $this->client->request('GET', '/mon-compte/editer')->filter('form[name="user_information_form"]')->form();
    }

    private function reloadUser(): User
    {
        $this->entityManager()->clear();
        $user = $this->entityManager()->find(User::class, $this->userId);
        self::assertInstanceOf(User::class, $user);

        return $user;
    }
}
