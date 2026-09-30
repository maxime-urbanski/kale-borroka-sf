<?php

declare(strict_types=1);

namespace App\Tests\Controller\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;

class EmailCaseTest extends WebTestCase
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

    public function testLoginIgnoresTheCaseOfTheEmail(): void
    {
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->filter('form[method="post"]')->form([
            'email' => ' TEST@Test.fr ',
            'password' => 'password123',
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects();
        self::assertStringNotContainsString('/login', (string) $this->client->getResponse()->headers->get('Location'));
        $this->client->request('GET', '/mon-compte');
        self::assertResponseIsSuccessful();
    }

    public function testRegisteringTheSameEmailInAnotherCaseIsRefused(): void
    {
        $users = self::getContainer()->get(UserRepository::class);
        $count = $users->count();

        $crawler = $this->client->request('GET', '/register');
        $form = $crawler->filter('form')->last()->form();
        $prefix = $form->getName();
        $form->setValues([
            $prefix.'[lastname]' => 'Doe',
            $prefix.'[firstname]' => 'Jane',
            $prefix.'[email]' => 'Test@TEST.fr',
            $prefix.'[plainPassword]' => 'password123',
        ]);
        $agreeTerms = $form[$prefix.'[agreeTerms]'];
        self::assertInstanceOf(ChoiceFormField::class, $agreeTerms);
        $agreeTerms->tick();
        $this->client->submit($form);

        self::assertSelectorTextContains('form', 'There is already an account with this email');
        self::assertSame($count, $users->count());
    }

    public function testEmailsAreStoredLowercased(): void
    {
        self::assertSame('jane@example.org', (new User())->setEmail(' Jane@Example.ORG ')->getEmail());
    }
}
