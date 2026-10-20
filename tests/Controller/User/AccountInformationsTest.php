<?php

declare(strict_types=1);

namespace App\Tests\Controller\User;

use App\Entity\User;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
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
        self::assertSelectorTextContains('body', 'nouveau@test.fr');

        $this->client->request('GET', $link);

        self::assertResponseRedirects('/mon-compte/editer');
        self::assertSame('nouveau@test.fr', $this->reloadUser()->getEmail());
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
        $this->client->submit($form);

        self::assertResponseRedirects('/mon-compte/editer');
        self::assertQueuedEmailCount(1);
        $mail = self::getMailerMessage();
        self::assertInstanceOf(Email::class, $mail);
        self::assertSame(mb_strtolower($email), $mail->getTo()[0]->getAddress());
        self::assertSame(1, preg_match('~https?://\S+/mon-compte/confirmer-email\S+~', (string) $mail->getTextBody(), $match));

        return $match[0];
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
