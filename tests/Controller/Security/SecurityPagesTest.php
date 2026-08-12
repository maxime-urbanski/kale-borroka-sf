<?php

declare(strict_types=1);

namespace App\Tests\Controller\Security;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * These pages are the only ones building validation constraints by hand. Symfony 8 no
 * longer accepts an array of options in a constraint constructor, which turned all three
 * of them into a 500 without any test noticing.
 */
class SecurityPagesTest extends WebTestCase
{
    private ?KernelBrowser $client = null;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient();
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function pageProvider(): iterable
    {
        yield 'login' => ['/login', 'form[method="post"]'];
        yield 'register' => ['/register', 'form[name="registration_form"]'];
        yield 'reset password request' => ['/reset-password', 'form[name="reset_password_request_form"]'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pageProvider')]
    public function testPageRendersItsForm(string $uri, string $formSelector): void
    {
        $crawler = $this->client->request('GET', $uri);

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter($formSelector));
    }
}
