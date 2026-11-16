<?php

declare(strict_types=1);

namespace App\Tests\Controller\Order;

use App\Entity\Article;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Step 2 of the checkout (mockup « Tunnel d'achat »): stepper, address and payment as choice
 * cards, a free message, recap of the order. No transport choice: the shipping cost is worked out
 * by hand and mailed with the amount to pay.
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

        foreach (['deliveryAddress', 'paymentMethod'] as $field) {
            self::assertGreaterThan(0, $crawler->filter(\sprintf('.kb-choice input[type="radio"][name$="[%s]"]', $field))->count(), $field);
        }
        self::assertCount(0, $crawler->filter('input[name$="[transporter]"]'));
        self::assertCount(3, $crawler->filter('fieldset.kb-fieldset'));
        self::assertStringContainsString('Où on t\'envoie ça ?', $crawler->filter('fieldset.kb-fieldset')->eq(0)->text());
        self::assertStringContainsString('Comment tu veux payer ?', $crawler->filter('fieldset.kb-fieldset')->eq(1)->text());
    }

    public function testThePaymentCardsExplainEachWay(): void
    {
        $crawler = $this->open();

        $cards = $crawler->filter('input[name$="[paymentMethod]"] + .kb-choice__card');
        self::assertCount(2, $cards);
        self::assertStringContainsString('PayPal.me', $cards->eq(0)->text());
        self::assertStringContainsString('annonce réservée', $cards->eq(1)->text());
    }

    public function testPayPalIsTickedByDefault(): void
    {
        $crawler = $this->open();

        $checked = $crawler->filter('input[name$="[paymentMethod]"]:checked');
        self::assertCount(1, $checked);
        self::assertSame('PayPal', trim($crawler->filter(\sprintf('label[for="%s"] .kb-choice__title', $checked->attr('id')))->text()));
    }

    public function testTheMessageIsOptionalAndCapped(): void
    {
        $crawler = $this->open();

        $message = $crawler->filter('textarea[name$="[message]"], input[name$="[message]"]');
        self::assertCount(1, $message);
        self::assertSame('280', $message->attr('maxlength'));
        self::assertNull($message->attr('required'));
    }

    public function testTheMessageCannotPassTheLimit(): void
    {
        $crawler = $this->open();
        $form = $crawler->filter('form.kb-order__form')->form();
        $values = $form->getPhpValues();
        $name = (string) array_key_first($values);
        $values[$name]['message'] = str_repeat('a', 281);

        $this->client->request('POST', '/order/delivery', $values);

        self::assertResponseIsUnprocessable();
    }

    public function testNoPaymentIsTakenNow(): void
    {
        $crawler = $this->open();

        self::assertStringContainsString('Aucun paiement maintenant', $crawler->filter('.kb-order__notice')->text());
        self::assertStringContainsString('port', $crawler->filter('.kb-recap')->text());
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

    public function testTheDefaultAddressCarriesItsTag(): void
    {
        $crawler = $this->open();

        $default = (string) $this->user('test@test.fr')->getDefaultAddress()?->getId();
        $card = $crawler->filter(\sprintf('input[name$="[deliveryAddress]"][value="%s"]', $default))->closest('.kb-choice');
        self::assertStringContainsString('Par défaut', (string) $card?->text());
        self::assertCount(1, $crawler->filter('.kb-choice:contains("Par défaut")'));
    }

    /**
     * The radio's state is drawn by CSS from :checked, so picking another card moves it:
     * both marks are in every card, none is chosen by the server.
     */
    public function testEveryCardCarriesBothRadioMarks(): void
    {
        $crawler = $this->open();

        $cards = $crawler->filter('.kb-choice');
        self::assertCount($cards->count(), $crawler->filter('.kb-choice .kb-choice__mark .bi-square'));
        self::assertCount($cards->count(), $crawler->filter('.kb-choice .kb-choice__mark .bi-check-square-fill'));
    }

    public function testTheRecapListsTheLinesAndPointsToTheAddressBook(): void
    {
        $crawler = $this->open();

        self::assertCount(1, $crawler->filter('.kb-recap .kb-recap__line'));
        self::assertStringContainsString('2 × ', $crawler->filter('.kb-recap__line')->text());
        self::assertSame('/mon-compte/mes-adresses?ajouter', $crawler->filter('a:contains("Ajouter une adresse")')->attr('href'));
        self::assertSame('/cart', $crawler->filter('a:contains("Retour au panier")')->attr('href'));
    }

    public function testAMissingChoiceIsShownUnderItsFieldset(): void
    {
        $crawler = $this->open();
        $form = $crawler->filter('form.kb-order__form')->form();
        $values = $form->getPhpValues();
        $name = (string) array_key_first($values);
        unset($values[$name]['paymentMethod']);

        $crawler = $this->client->request('POST', '/order/delivery', $values);

        self::assertResponseIsUnprocessable();
        self::assertCount(1, $crawler->filter('fieldset.kb-fieldset .invalid-feedback, fieldset.kb-fieldset .form-error-message'));
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
