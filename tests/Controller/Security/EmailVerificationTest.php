<?php

declare(strict_types=1);

namespace App\Tests\Controller\Security;

use App\Entity\User;
use App\Order\Exception\InvalidOrderException;
use App\Repository\UserRepository;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\Mime\Email;

/**
 * An order waits until the customer proved they read their address: the label mails them the
 * payment link and the parcel's tracking.
 */
class EmailVerificationTest extends WebTestCase
{
    use OrderTestTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
        $this->beginIsolation();
        // The registration limiter state is in a cache pool that outlives the test.
        self::service(AdapterInterface::class, 'cache.rate_limiter')->clear();
    }

    protected function tearDown(): void
    {
        $this->endIsolation();
        parent::tearDown();
    }

    public function testRegisteringMailsALinkAndTheAccountStartsUnverified(): void
    {
        $user = $this->register();

        self::assertFalse($user->isEmailVerified());
        self::assertQueuedEmailCount(1);
        $mail = self::getMailerMessage();
        self::assertInstanceOf(Email::class, $mail);
        self::assertSame('jane@example.test', $mail->getTo()[0]->getAddress());
        self::assertNotNull($this->linkOf($mail));
    }

    public function testOpeningTheLinkVerifiesTheAddress(): void
    {
        $user = $this->register();
        $link = (string) $this->linkOf(self::getMailerMessage());

        // Opening it (or a mail scanner opening it) changes nothing: the button does.
        $crawler = $this->client->request('GET', $link);
        self::assertResponseIsSuccessful();
        self::assertFalse($this->reload($user)->isEmailVerified());
        $this->client->submit($crawler->selectButton('Confirmer mon adresse')->form());

        self::assertResponseRedirects();
        self::assertTrue($this->reload($user)->isEmailVerified());
        // Twice is harmless.
        $crawler = $this->client->request('GET', $link);
        $this->client->submit($crawler->selectButton('Confirmer mon adresse')->form());
        self::assertTrue($this->reload($user)->isEmailVerified());
    }

    public function testTheButtonNeedsItsToken(): void
    {
        $user = $this->register();
        $link = (string) $this->linkOf(self::getMailerMessage());

        $this->client->request('POST', $link, ['_token' => 'forged']);

        self::assertFalse($this->reload($user)->isEmailVerified());
    }

    public function testAnAlteredLinkVerifiesNothing(): void
    {
        $user = $this->register();
        $link = (string) $this->linkOf(self::getMailerMessage());
        $other = $this->user('test@test.fr');

        foreach ([$link.'x', preg_replace('/id=\d+/', 'id='.$other->getId(), $link)] as $forged) {
            $this->client->request('POST', (string) $forged);
            self::assertResponseRedirects();
            self::assertFalse($this->reload($user)->isEmailVerified());
        }
    }

    public function testALinkDiesWhenTheAddressChanges(): void
    {
        $user = $this->register();
        $link = (string) $this->linkOf(self::getMailerMessage());
        $this->entityManager()->find(User::class, $user->getId())?->setEmail('other@example.test');
        $this->entityManager()->flush();

        $this->client->request('POST', $link);

        self::assertResponseRedirects();
        self::assertFalse($this->reload($user)->isEmailVerified());
    }

    public function testAnUnverifiedCustomerCannotReachTheCheckout(): void
    {
        $user = $this->register();
        $this->client->loginUser($this->reload($user));

        $this->client->request('GET', '/order/delivery');

        self::assertResponseRedirects('/cart');
        $this->client->followRedirect();
        self::assertSelectorExists('#email-non-confirme');
    }

    public function testTheUnverifiedFixtureAccountIsBlockedAtTheCheckout(): void
    {
        $user = $this->user('nonverifie@test.fr');
        self::assertFalse($user->isEmailVerified());
        $this->client->loginUser($user);

        $this->client->request('GET', '/order/delivery');

        self::assertResponseRedirects('/cart');
    }

    public function testAVerifiedCustomerReachesTheCheckout(): void
    {
        $this->client->loginUser($this->user('test@test.fr'));

        $this->client->request('GET', '/order/delivery');

        self::assertResponseIsSuccessful();
    }

    public function testThePlaceOrderCommandRefusesAnUnverifiedBuyer(): void
    {
        $user = $this->register();
        [$release] = $this->releasesWithStock(3);

        $this->expectException(InvalidOrderException::class);
        $this->placeOrder($this->reload($user), [self::idOf($release) => 1]);
    }

    public function testResendMailsANewLinkOnceTheTokenIsRight(): void
    {
        $user = $this->register();
        $this->client->loginUser($this->reload($user));
        $crawler = $this->client->request('GET', '/mon-compte');
        $form = $crawler->filter('#email-non-confirme form')->form();

        $this->client->submit($form);

        self::assertResponseRedirects();
        self::assertQueuedEmailCount(1);
    }

    public function testResendWithAForgedTokenSendsNothing(): void
    {
        $user = $this->register();
        $this->client->loginUser($this->reload($user));

        $this->client->request('POST', '/mon-compte/verification-email', ['_token' => 'forged']);

        self::assertResponseRedirects();
        self::assertQueuedEmailCount(0);
    }

    public function testResendIsLimitedPerAccount(): void
    {
        $user = $this->register();
        $this->client->loginUser($this->reload($user));

        // 3 per hour: the fourth click mails nothing and says why.
        for ($i = 0; $i < 4; ++$i) {
            $crawler = $this->client->request('GET', '/mon-compte');
            $this->client->submit($crawler->filter('#email-non-confirme form')->form());
        }

        self::assertQueuedEmailCount(0);
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-danger, [role="alert"]', 'Trop de mails');
    }

    public function testChangingTheAddressDropsTheProof(): void
    {
        $user = $this->user('test@test.fr');
        self::assertTrue($user->isEmailVerified());

        $user->setEmail('test@test.fr');
        self::assertTrue($user->isEmailVerified(), 'same address, same proof');

        $user->setEmail('changed@example.test');
        self::assertFalse($user->isEmailVerified());

        $user->markEmailVerified();
        self::assertTrue($user->isEmailVerified());
    }

    public function testRegistrationIsLimitedPerConnection(): void
    {
        for ($i = 1; $i <= 5; ++$i) {
            $this->register("jane{$i}@example.test");
            $this->client->request('GET', '/logout');
            $this->client->getCookieJar()->clear();
        }

        $this->submitRegistration('jane6@example.test');

        self::assertSelectorTextContains('form[name="registration_form"]', 'Trop d’inscriptions');
        self::assertNull(self::service(UserRepository::class)->findOneByEmail('jane6@example.test'));
    }

    public function testTheMailCarriesNothingTheVisitorTyped(): void
    {
        $this->register();
        $mail = self::getMailerMessage();
        self::assertInstanceOf(Email::class, $mail);

        self::assertStringNotContainsString('Jane', (string) $mail->getTextBody());
        self::assertStringNotContainsString('Jane', (string) $mail->getHtmlBody());
    }

    private function register(string $email = 'jane@example.test'): User
    {
        $this->submitRegistration($email);
        self::assertResponseRedirects();

        $user = self::service(UserRepository::class)->findOneByEmail($email);
        self::assertInstanceOf(User::class, $user);

        return $user;
    }

    private function submitRegistration(string $email): void
    {
        $crawler = $this->client->request('GET', '/register');
        $form = $crawler->filter('form[name="registration_form"]')->form();
        $prefix = $form->getName();
        $form->setValues([
            $prefix.'[lastname]' => 'Doe',
            $prefix.'[firstname]' => 'Jane',
            $prefix.'[email]' => $email,
            $prefix.'[plainPassword]' => 'Kale-Borroka-2026!',
        ]);
        $agreeTerms = $form[$prefix.'[agreeTerms]'];
        self::assertInstanceOf(ChoiceFormField::class, $agreeTerms);
        $agreeTerms->tick();
        $this->client->submit($form);
    }

    private function reload(User $user): User
    {
        $this->entityManager()->clear();
        $reloaded = $this->entityManager()->find(User::class, $user->getId());
        self::assertInstanceOf(User::class, $reloaded);

        return $reloaded;
    }

    private function linkOf(mixed $mail): ?string
    {
        self::assertInstanceOf(Email::class, $mail);

        return preg_match('~https?://\S+/verification-email\?\S+~', (string) $mail->getTextBody(), $matches) ? $matches[0] : null;
    }
}
