<?php

declare(strict_types=1);

namespace App\Tests\Controller\User;

use App\Entity\Address;
use App\Entity\User;
use App\Repository\AddressRepository;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Account pages: a customer only touches their own addresses, and changing the e-mail
 * or the password goes through validation, CSRF and the current password.
 */
class AccountSecurityTest extends WebTestCase
{
    use OrderTestTrait;

    private ?KernelBrowser $client = null;

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

    public function testAnotherCustomersAddressCannotBeEdited(): void
    {
        $victim = $this->addressOf('client1@kaleborroka.test');
        $name = $victim->getName();
        $token = $this->ownAddressForm()['user_account_address_form[_token]']->getValue();

        $this->client->request('PATCH', \sprintf('/mon-compte/mes-adresses/update/%d', $victim->getId()), ['user_account_address_form' => [
            '_token' => $token,
            'name' => 'Piraté',
        ]]);

        self::assertResponseStatusCodeSame(403);
        self::assertSame($name, $this->reload($victim)->getName());
    }

    public function testOwnAddressCanBeEdited(): void
    {
        $form = $this->ownAddressForm();
        $form['user_account_address_form[name]'] = 'Chez moi';
        $this->client->submit($form);

        self::assertResponseRedirects('/mon-compte/mes-adresses');
        self::assertSame('Chez moi', $this->reload($this->addressOf('test@test.fr'))->getName());
    }

    public function testAnotherCustomersAddressCannotBeDeletedOrMadeDefault(): void
    {
        // No order points at it: nothing but the voter stands in the way of deleting it.
        $victim = (new Address())->setName('Boulot')->setAddress('1 rue de la Paix')->setComplementAddress('')
            ->setZipcode('64100')->setCity('Bayonne')->setCountry('France')->setIsMainAddress(false)->setUsers($this->user('client1@kaleborroka.test'));
        $this->entityManager()->persist($victim);
        $this->entityManager()->flush();
        $id = $victim->getId();
        // A valid token for that address in the attacker's session: only the voter is left.
        $token = $this->csrfToken('address-'.$id);

        $this->client->request('DELETE', \sprintf('/mon-compte/mes-adresses/remove/%d', $id), server: ['HTTP_X_CSRF_TOKEN' => $token]);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('PATCH', \sprintf('/mon-compte/mes-adresses/update/default-address/0/%d', $id), server: ['HTTP_X_CSRF_TOKEN' => $token]);
        self::assertResponseStatusCodeSame(403);

        $this->entityManager()->clear();
        $address = $this->entityManager()->find(Address::class, $id);
        self::assertInstanceOf(Address::class, $address, 'not deleted');
        self::assertSame('client1@kaleborroka.test', $address->getUsers()?->getEmail());
    }

    public function testDeletingAnAddressNeedsItsCsrfToken(): void
    {
        $own = $this->addressOf('test@test.fr');
        $crawler = $this->client->request('GET', '/mon-compte/mes-adresses');
        $token = $crawler->filter(\sprintf('[data-address-method-value="DELETE"][data-address-id-value="%d"]', $own->getId()))->attr('data-address-token-value');

        $this->client->request('DELETE', \sprintf('/mon-compte/mes-adresses/remove/%d', $own->getId()), server: ['HTTP_X_CSRF_TOKEN' => 'forged']);
        self::assertResponseRedirects('/mon-compte/mes-adresses', null, 'back to the address book, not to the login page');
        $this->client->followRedirect();
        self::assertSelectorTextContains('body', 'La page a expiré');
        $this->entityManager()->clear();
        self::assertNotNull($this->entityManager()->find(Address::class, $own->getId()), 'a forged token deletes nothing');

        $this->client->request('DELETE', \sprintf('/mon-compte/mes-adresses/remove/%d', $own->getId()), server: ['HTTP_X_CSRF_TOKEN' => (string) $token]);
        self::assertResponseRedirects('/mon-compte/mes-adresses');
        $this->entityManager()->clear();
        self::assertNull($this->entityManager()->find(Address::class, $own->getId()));
    }

    public function testDefaultAddressIsShownEscaped(): void
    {
        $address = $this->addressOf('test@test.fr')->setName('<img src=x onerror=alert(1)>')->setIsMainAddress(true);
        $this->entityManager()->flush();

        $this->client->request('GET', '/mon-compte');

        self::assertResponseIsSuccessful();
        $html = (string) $this->client->getResponse()->getContent();
        self::assertStringNotContainsString('<img src=x', $html);
        self::assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;<br />', $html);
        self::assertStringContainsString((string) $address->getCity(), $html);
    }

    public function testEmailChangeIsValidatedAndProtectedByCsrf(): void
    {
        $user = $this->user('test@test.fr');
        $form = $this->client->request('GET', '/mon-compte/editer')->filter('form[name="user_information_form"]')->form();
        $values = $form->getPhpValues();

        // Forged request: no valid token.
        $forged = $values;
        $forged['user_information_form']['_token'] = 'forged';
        $forged['user_information_form']['email'] = 'attacker@example.com';
        $this->client->request($form->getMethod(), $form->getUri(), $forged);
        self::assertResponseIsSuccessful();
        self::assertSame('test@test.fr', $this->reloadUser($user)->getEmail());

        // Someone else's e-mail.
        $values['user_information_form']['email'] = 'client1@kaleborroka.test';
        $this->client->request($form->getMethod(), $form->getUri(), $values);
        self::assertSelectorTextContains('form[name="user_information_form"]', 'Un compte utilise déjà cette adresse e-mail.');
        self::assertSame('test@test.fr', $this->reloadUser($user)->getEmail());

        // Not an e-mail.
        $values['user_information_form']['email'] = 'pas-un-email';
        $this->client->request($form->getMethod(), $form->getUri(), $values);
        self::assertSame('test@test.fr', $this->reloadUser($user)->getEmail());

        $values['user_information_form']['email'] = 'nouveau@test.fr';
        $this->client->request($form->getMethod(), $form->getUri(), $values);
        self::assertResponseRedirects('/mon-compte');
        self::assertSame('nouveau@test.fr', $this->reloadUser($user)->getEmail());
    }

    public function testPasswordChangeRequiresTheCurrentPassword(): void
    {
        $user = $this->user('test@test.fr');
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        $form = $this->client->request('GET', '/mon-compte/editer-mot-de-passe')->filter('form[name="update_password_form"]')->form();
        $form['update_password_form[current_password]'] = 'mauvais';
        $form['update_password_form[new_password][first]'] = 'Kale-Borroka-2026!';
        $form['update_password_form[new_password][second]'] = 'Kale-Borroka-2026!';
        $this->client->submit($form);

        self::assertSelectorTextContains('form[name="update_password_form"]', 'Mot de passe actuel incorrect.');
        self::assertTrue($hasher->isPasswordValid($this->reloadUser($user), 'password123'));

        $form = $this->client->getCrawler()->filter('form[name="update_password_form"]')->form();
        $form['update_password_form[current_password]'] = 'password123';
        $form['update_password_form[new_password][first]'] = 'Kale-Borroka-2026!';
        $form['update_password_form[new_password][second]'] = 'Kale-Borroka-2026!';
        $this->client->submit($form);

        self::assertResponseRedirects('/mon-compte');
        self::assertTrue($hasher->isPasswordValid($this->reloadUser($user), 'Kale-Borroka-2026!'));
    }

    /**
     * A token as the page would have rendered it, stored in the browser's session.
     */
    private function csrfToken(string $id): string
    {
        $this->client->request('GET', '/mon-compte/mes-adresses');
        $container = self::getContainer();
        $session = $container->get('session.factory')->createSession();
        $session->setId((string) $this->client->getCookieJar()->get($session->getName())?->getValue());
        $session->start();
        $request = new Request();
        $request->setSession($session);
        $container->get('request_stack')->push($request);

        try {
            return $container->get('security.csrf.token_manager')->getToken($id)->getValue();
        } finally {
            $session->save();
            $container->get('request_stack')->pop();
        }
    }

    private function ownAddressForm(): \Symfony\Component\DomCrawler\Form
    {
        $own = $this->addressOf('test@test.fr');
        $crawler = $this->client->request('GET', '/mon-compte/mes-adresses');

        return $crawler->filter(\sprintf('form[action$="/mon-compte/mes-adresses/update/%d"]', $own->getId()))->form();
    }

    private function addressOf(string $email): Address
    {
        $address = self::getContainer()->get(AddressRepository::class)->findOneBy(['users' => $this->user($email)]);
        self::assertInstanceOf(Address::class, $address);

        return $address;
    }

    private function reload(Address $address): Address
    {
        $this->entityManager()->clear();
        $reloaded = $this->entityManager()->find(Address::class, $address->getId());
        self::assertInstanceOf(Address::class, $reloaded);

        return $reloaded;
    }

    private function reloadUser(User $user): User
    {
        $this->entityManager()->clear();
        $reloaded = $this->entityManager()->find(User::class, $user->getId());
        self::assertInstanceOf(User::class, $reloaded);

        return $reloaded;
    }
}
