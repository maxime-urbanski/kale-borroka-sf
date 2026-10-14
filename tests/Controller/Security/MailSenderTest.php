<?php

declare(strict_types=1);

namespace App\Tests\Controller\Security;

use App\Tests\DatabaseIsolationTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * The SMTP server only relays mail sent from its own mailbox (OVH's Zimbra in production),
 * so the sender comes from MAILER_DEFAULT_SENDER rather than from the code.
 */
class MailSenderTest extends WebTestCase
{
    use DatabaseIsolationTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
        // The reset request is stored (and throttled) in the database: roll it back.
        $this->beginIsolation();
    }

    protected function tearDown(): void
    {
        $this->endIsolation();
        parent::tearDown();
    }

    public function testThePasswordResetMailComesFromTheConfiguredSender(): void
    {
        $crawler = $this->client->request('GET', '/reset-password');
        $form = $crawler->filter('form[name="reset_password_request_form"]')->form([
            'reset_password_request_form[email]' => 'test@test.fr',
        ]);
        $this->client->submit($form);

        self::assertEmailCount(1);
        $email = self::getMailerMessage();
        self::assertInstanceOf(Email::class, $email);
        self::assertEquals([new Address('shop@example.org', 'Kale Borroka Records')], $email->getFrom());
    }
}
