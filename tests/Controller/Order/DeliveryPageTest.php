<?php

declare(strict_types=1);

namespace App\Tests\Controller\Order;

use App\Entity\Article;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Step 2 of the checkout (mockup « Tunnel d'achat »): stepper, address / transport / payment
 * as choice cards, recap of the order.
 */
class DeliveryPageTest extends WebTestCase
{
    use OrderTestTrait;

    private KernelBrowser $client;

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

    public function testTheStepperMarksTheOrderStep(): void
    {
        $crawler = $this->open();

        self::assertSame('Commande', trim($crawler->filter('.kb-steps__step[aria-current="step"] .kb-steps__label')->text()));
        self::assertCount(1, $crawler->filter('.kb-steps__step--done'));
    }

    public function testEachChoiceIsACard(): void
    {
        $crawler = $this->open();

        foreach (['deliveryAddress', 'transporter', 'paymentMethod'] as $field) {
            self::assertGreaterThan(0, $crawler->filter(\sprintf('.kb-choice input[type="radio"][name$="[%s]"]', $field))->count(), $field);
        }
        self::assertCount(3, $crawler->filter('fieldset.kb-fieldset'));
    }

    public function testTheDefaultAddressIsPreselected(): void
    {
        $user = $this->user('test@test.fr');
        $default = $user->getDefaultAddress();
        self::assertNotNull($default);
        $this->client->loginUser($user);
        $this->put($this->releasesWithStock(3)[0]);

        $crawler = $this->client->request('GET', '/order/delivery');

        $checked = $crawler->filter('input[type="radio"][name$="[deliveryAddress]"]:checked');
        self::assertCount(1, $checked);
        self::assertSame((string) $default->getId(), $checked->attr('value'));
    }

    public function testTheRecapListsTheLinesAndPointsToTheAddressBook(): void
    {
        $crawler = $this->open();

        self::assertCount(1, $crawler->filter('.kb-recap .kb-recap__line'));
        self::assertStringContainsString('×2', $crawler->filter('.kb-recap__line')->text());
        self::assertSame('/mon-compte/mes-adresses?ajouter', $crawler->filter('a:contains("Nouvelle adresse")')->attr('href'));
        self::assertSame('/cart', $crawler->filter('a:contains("Retour au panier")')->attr('href'));
    }

    public function testAMissingChoiceIsShownUnderItsFieldset(): void
    {
        $crawler = $this->open();
        $form = $crawler->filter('form.kb-order__form')->form();
        $values = $form->getPhpValues();
        $name = (string) array_key_first($values);
        unset($values[$name]['transporter'], $values[$name]['paymentMethod']);

        $crawler = $this->client->request('POST', '/order/delivery', $values);

        self::assertResponseIsUnprocessable();
        self::assertCount(2, $crawler->filter('fieldset.kb-fieldset .invalid-feedback, fieldset.kb-fieldset .form-error-message'));
    }

    private function open(): Crawler
    {
        $this->client->loginUser($this->user('test@test.fr'));
        $this->put($this->releasesWithStock(5)[0]);

        return $this->client->request('GET', '/order/delivery');
    }

    private function put(Article $article): void
    {
        $crawler = $this->client->request('GET', \sprintf('/catalog/%s/%s', $article->getSupportType()?->value, $article->getSlug()));
        $this->client->submit($crawler->filter('form[name="add_to_cart_with_quantity"]')->form([
            'add_to_cart_with_quantity[quantity]' => '2',
        ]));
    }
}
