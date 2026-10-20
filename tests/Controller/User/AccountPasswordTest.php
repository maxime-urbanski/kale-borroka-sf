<?php

declare(strict_types=1);

namespace App\Tests\Controller\User;

use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Mot de passe (maquette 15): current password, the new one with its rules, its confirmation;
 * changing it signs every other device out.
 */
class AccountPasswordTest extends WebTestCase
{
    use OrderTestTrait;

    private const string NEW_PASSWORD = 'Kale-Borroka-2026!';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
        $this->beginIsolation();
        $this->client->loginUser($this->user('test@test.fr'));
    }

    protected function tearDown(): void
    {
        $this->endIsolation();
        parent::tearDown();
    }

    public function testThePageHelpsPasswordManagersAndForgetfulPeople(): void
    {
        $crawler = $this->client->request('GET', '/mon-compte/editer-mot-de-passe');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Mot de passe');
        $form = $crawler->filter('form[name="update_password_form"]');
        // The account the new password belongs to, for password managers.
        self::assertSame('test@test.fr', $form->filter('input[autocomplete="username"]')->attr('value'));
        self::assertSame('current-password', $form->filter('#update_password_form_current_password')->attr('autocomplete'));
        self::assertSame('new-password', $form->filter('#update_password_form_new_password_second')->attr('autocomplete'));
        self::assertCount(1, $form->filter('a[href$="/reset-password"]'), 'Oublié ?');
        self::assertCount(2, $form->filter('.kb-password-rules li'));
    }

    public function testTwoDifferentPasswordsAreRefused(): void
    {
        $form = $this->form();
        $form['update_password_form[new_password][second]'] = 'Autre-chose-2026!';
        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form[name="update_password_form"]', 'Les deux mots de passe diffèrent.');
        self::assertTrue($this->passwordIs('password123'));
    }

    public function testChangingThePasswordSignsTheOtherDevicesOut(): void
    {
        // Another device: a session of its own (loginUser() reuses the current one).
        $this->client->request('GET', '/mon-compte');
        $other = $this->sessionCookie();
        $this->client->getCookieJar()->clear();
        $this->client->loginUser($this->user('test@test.fr'));
        $this->client->request('GET', '/mon-compte');
        self::assertNotSame($other->getValue(), $this->sessionCookie()->getValue());

        $this->client->submit($this->form());

        self::assertResponseRedirects('/mon-compte/editer-mot-de-passe');
        self::assertTrue($this->passwordIs(self::NEW_PASSWORD));
        // Its own remember-me cookie, signed with the old hash, is replaced.
        self::assertResponseHasCookie('REMEMBERME');
        $this->client->followRedirect();
        self::assertSelectorTextContains('body', 'Mot de passe changé');

        $this->client->request('GET', '/mon-compte/editer-mot-de-passe');
        self::assertResponseIsSuccessful('this device stays signed in');

        $this->client->getCookieJar()->set($other);
        $this->client->request('GET', '/mon-compte/editer-mot-de-passe');
        self::assertResponseRedirects('/login', null, 'the other device is signed out');
    }

    /**
     * The form, filled with the right current password and a valid new one.
     */
    private function form(): Form
    {
        $form = $this->client->request('GET', '/mon-compte/editer-mot-de-passe')->filter('form[name="update_password_form"]')->form();
        $form['update_password_form[current_password]'] = 'password123';
        $form['update_password_form[new_password][first]'] = self::NEW_PASSWORD;
        $form['update_password_form[new_password][second]'] = self::NEW_PASSWORD;

        return $form;
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

    private function passwordIs(string $password): bool
    {
        $this->entityManager()->clear();

        return self::service(UserPasswordHasherInterface::class)->isPasswordValid($this->user('test@test.fr'), $password);
    }
}
