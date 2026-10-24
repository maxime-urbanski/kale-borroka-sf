<?php

declare(strict_types=1);

namespace App\Tests\Controller\User;

use App\Entity\Article;
use App\Entity\Order;
use App\Entity\User;
use App\Enum\OrderTransition;
use App\Order\Command\ApplyOrderTransition;
use App\Repository\UserCollectionItemsRepository;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mes commandes (maquettes 16, 17, 18): the list in two crates, « En cours » then
 * « Terminées » paginated by 9, the detail of an order with where it stands and what comes
 * next, the thank-you banner after the checkout, and the two actions of a finished order.
 */
class AccountOrdersTest extends WebTestCase
{
    use OrderTestTrait;

    private const string CUSTOMER = 'test@test.fr';
    private const string OTHER_CUSTOMER = 'maxiloud@gmail.com';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        // Same kernel for every request, so they all see the test transaction.
        $this->client->disableReboot();
        $this->beginIsolation();
    }

    protected function tearDown(): void
    {
        $this->endIsolation();
        parent::tearDown();
    }

    public function testOngoingOrdersComeFirstAndFinishedOnesApart(): void
    {
        [$release] = $this->releasesWithStock(10);
        $pending = $this->placeOrder($this->customer(), [self::idOf($release) => 1]);
        $shipped = $this->placeOrder($this->customer(), [self::idOf($release) => 2]);
        $delivered = $this->placeOrder($this->customer(), [self::idOf($release) => 1]);
        $this->apply($shipped, OrderTransition::PAY, OrderTransition::SHIP);
        $this->apply($delivered, OrderTransition::PAY, OrderTransition::SHIP, OrderTransition::DELIVER);

        $crawler = $this->orders();

        self::assertSame([$shipped->getReference(), $pending->getReference()], $this->references($crawler, '#en-cours'));
        self::assertSame([$delivered->getReference()], $this->references($crawler, '#terminees'));
        self::assertSelectorTextContains('.kb-account__since', '3 commandes');
        self::assertSelectorTextContains('#en-cours', '2 · dont 1 attend ton paiement');
    }

    public function testAPendingOrderSaysHowToPay(): void
    {
        [$release] = $this->releasesWithStock(5);
        $pending = $this->placeOrder($this->customer(), [self::idOf($release) => 1]);

        $crawler = $this->orders();

        $row = $crawler->filter('#en-cours tbody tr')->first();
        self::assertStringContainsString('Le lien de paiement arrive par mail', $row->text());
        self::assertSame($this->detailUri($pending).'#paiement', $row->filter('a')->attr('href'));
    }

    public function testFinishedOrdersArePaginatedByNine(): void
    {
        [$release] = $this->releasesWithStock(5);
        for ($i = 0; $i < 10; ++$i) {
            $this->apply($this->placeOrder($this->customer(), [self::idOf($release) => 1]), OrderTransition::CANCEL);
        }
        $pending = $this->placeOrder($this->customer(), [self::idOf($release) => 1]);

        $crawler = $this->orders();
        self::assertCount(9, $crawler->filter('#terminees tbody tr'));
        self::assertSame([$pending->getReference()], $this->references($crawler, '#en-cours'));
        self::assertSelectorTextContains('#terminees', '1–9 sur 10');

        $crawler = $this->orders('/page-2');
        self::assertCount(1, $crawler->filter('#terminees tbody tr'));
        self::assertSame([$pending->getReference()], $this->references($crawler, '#en-cours'), 'the ongoing ones stay whole on every page');
    }

    public function testACustomerOnlySeesTheirOwnOrders(): void
    {
        [$release] = $this->releasesWithStock(5);
        $theirs = $this->placeOrder($this->user(self::OTHER_CUSTOMER), [self::idOf($release) => 1]);

        $this->orders();

        self::assertSelectorTextNotContains('main', (string) $theirs->getReference());
        self::assertSelectorTextContains('main', 'Pas encore de commande');
    }

    public function testTheDetailShowsWhatWasBoughtAndWhereItGoes(): void
    {
        [$release] = $this->releasesWithStock(5);
        $order = $this->placeOrder($this->customer(), [self::idOf($release) => 2]);

        $crawler = $this->detail($order);

        self::assertSelectorTextContains('h1', (string) $order->getReference());
        self::assertSelectorTextContains('.kb-order__head .kb-badge', 'En attente de paiement');
        $line = $crawler->filter('.kb-order-lines tbody tr')->first();
        self::assertStringContainsString((string) $release->getSku(), $line->text());
        self::assertStringContainsString('2 × '.$this->euros(1000), $line->text());
        self::assertStringContainsString($this->euros(2000), $line->text());
        self::assertSelectorTextContains('.kb-order-recap', $this->euros((int) $order->getTotalPrice()));
        self::assertSelectorTextContains('.kb-order-recap', $this->euros($order->getShippingPrice()));
        self::assertSelectorTextContains('.kb-order-side address', explode("\n", (string) $order->getShippingAddress())[0]);
        self::assertSelectorTextContains('.kb-order-side', (string) $order->getDelivery()?->getName());
    }

    public function testTheTimelineMarksWhereTheOrderStands(): void
    {
        [$release] = $this->releasesWithStock(5);
        $order = $this->placeOrder($this->customer(), [self::idOf($release) => 1]);

        $this->detail($order);
        self::assertSelectorTextContains('.kb-order-steps [aria-current="step"]', 'En attente de paiement');

        $this->apply($order, OrderTransition::PAY, OrderTransition::SHIP);
        $this->detail($order);
        self::assertSelectorTextContains('.kb-order-steps [aria-current="step"]', 'Expédiée');
        self::assertSelectorTextContains('.kb-order-steps', 'Payée (étape passée)');
        self::assertSelectorTextContains('.kb-order-action', 'Ton colis est en route');
    }

    public function testAPendingOrderExplainsTheManualPayment(): void
    {
        [$release] = $this->releasesWithStock(5);
        $order = $this->placeOrder($this->customer(), [self::idOf($release) => 1]);

        $this->detail($order);

        self::assertSelectorTextContains('#paiement', 'On vérifie le stock à la main');
        self::assertSelectorTextContains('#paiement .kb-order-ref', (string) $order->getReference());
        self::assertSelectorExists('#paiement [data-controller="clipboard"]');
    }

    public function testACancelledOrderLeavesTheCycle(): void
    {
        [$release] = $this->releasesWithStock(5);
        $order = $this->placeOrder($this->customer(), [self::idOf($release) => 1]);
        $this->apply($order, OrderTransition::CANCEL);

        $this->detail($order);

        self::assertSelectorTextContains('.kb-order-out', 'Annulée');
        self::assertSelectorTextContains('.kb-order-out', 'Rien n’a été encaissé');
        self::assertSelectorNotExists('.kb-order-steps [aria-current="step"]');
        self::assertSelectorTextContains('.kb-order-steps', 'Expédiée (non atteinte)');
    }

    public function testALineWhoseArticleLeftTheCatalogueKeepsItsName(): void
    {
        [$release] = $this->releasesWithStock(5);
        $order = $this->placeOrder($this->customer(), [self::idOf($release) => 1]);
        $release->setPublished(false);
        $this->entityManager()->flush();

        $crawler = $this->detail($order);

        $line = $crawler->filter('.kb-order-lines tbody tr')->first();
        self::assertStringContainsString('Retiré du catalogue', $line->text());
        self::assertCount(0, $line->filter('a'), 'no link to a page that is a 404 now');
    }

    public function testTheLabelCanBeWrittenToAboutTheOrder(): void
    {
        [$release] = $this->releasesWithStock(5);
        $order = $this->placeOrder($this->customer(), [self::idOf($release) => 1]);

        $crawler = $this->detail($order);

        $mailto = (string) $crawler->filter('.kb-order-contact a')->attr('href');
        self::assertStringStartsWith('mailto:contact@kaleborroka.test?subject=', $mailto);
        self::assertStringContainsString((string) $order->getReference(), rawurldecode($mailto));
    }

    public function testAnotherCustomerIsDenied(): void
    {
        [$release] = $this->releasesWithStock(5);
        $order = $this->placeOrder($this->user(self::OTHER_CUSTOMER), [self::idOf($release) => 1]);

        $this->client->loginUser($this->customer());
        $this->client->request('GET', $this->detailUri($order));

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAnonymousVisitorIsSentToTheLoginPage(): void
    {
        [$release] = $this->releasesWithStock(5);
        $order = $this->placeOrder($this->customer(), [self::idOf($release) => 1]);

        $this->client->request('GET', $this->detailUri($order));

        self::assertResponseRedirects('/login');
    }

    public function testAnAdminDoesNotSeeACustomersOrderInTheirOwnAccount(): void
    {
        // The back office shows every order; the account pages mix in the viewer's collection and counters.
        [$release] = $this->releasesWithStock(5);
        $order = $this->placeOrder($this->customer(), [self::idOf($release) => 1]);

        $this->client->loginUser($this->user(self::OTHER_CUSTOMER));
        $this->client->request('GET', $this->detailUri($order));

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testTheAccountMenuStaysOnOrders(): void
    {
        [$release] = $this->releasesWithStock(5);
        $order = $this->placeOrder($this->customer(), [self::idOf($release) => 1]);

        $crawler = $this->detail($order);

        self::assertSame('true', $crawler->filter('nav[aria-label="Mon compte"] a[href="/mon-compte/mes-commandes"]')->attr('aria-current'));
    }

    public function testARefundedOrderDoesNotClaimItWasNeverDelivered(): void
    {
        [$release] = $this->releasesWithStock(5);
        $order = $this->placeOrder($this->customer(), [self::idOf($release) => 1]);
        $this->apply($order, OrderTransition::PAY, OrderTransition::SHIP, OrderTransition::DELIVER, OrderTransition::REFUND);

        $this->detail($order);

        // Refunded from shipped or from delivered: no history says which.
        self::assertSelectorTextContains('.kb-order-steps', 'Expédiée (étape passée)');
        self::assertSelectorTextNotContains('.kb-order-steps', 'Livrée (non atteinte)');
        self::assertSelectorTextContains('.kb-order-steps', 'Livrée (non connue)');
    }

    public function testAPageOfFinishedOrdersPastTheLastIsNotFound(): void
    {
        [$release] = $this->releasesWithStock(5);
        $this->apply($this->placeOrder($this->customer(), [self::idOf($release) => 1]), OrderTransition::CANCEL);

        $this->client->loginUser($this->customer());
        $this->client->request('GET', '/mon-compte/mes-commandes/page-5');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUnknownReferenceIsNotFound(): void
    {
        $this->client->loginUser($this->customer());
        $this->client->request('GET', '/mon-compte/mes-commandes/KBR-nope');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testTheThankYouBannerOnlyShowsRightAfterTheCheckout(): void
    {
        [$release] = $this->releasesWithStock(5);
        $order = $this->checkout($release);

        self::assertResponseRedirects($this->detailUri($order));
        $this->client->followRedirect();
        self::assertSelectorTextContains('h1', 'Commande reçue');
        self::assertSelectorTextContains('.kb-order-thanks', self::CUSTOMER);
        self::assertSelectorTextContains('.kb-order-thanks .kb-order-ref', (string) $order->getReference());

        $this->client->request('GET', $this->detailUri($order));
        self::assertSelectorNotExists('.kb-order-thanks');
        self::assertSelectorTextContains('h1', (string) $order->getReference());
    }

    public function testAFinishedOrderGoesBackIntoTheCart(): void
    {
        [$inStock, $soldOut] = $this->releasesWithStock(5, 5);
        $order = $this->placeOrder($this->customer(), [self::idOf($inStock) => 2, self::idOf($soldOut) => 1]);
        $this->apply($order, OrderTransition::PAY, OrderTransition::SHIP, OrderTransition::DELIVER);
        $soldOut->setStock(0);
        $this->entityManager()->flush();

        $crawler = $this->detail($order);
        self::assertSelectorTextContains('.kb-order-again', '1 sur 2 encore en stock');
        $this->client->submit($crawler->filter('.kb-order-again form')->form());

        self::assertResponseRedirects('/cart', Response::HTTP_SEE_OTHER);
        $this->client->followRedirect();
        self::assertSelectorTextContains('main', (string) $inStock->getName());
        self::assertSelectorTextNotContains('main', (string) $soldOut->getName());
    }

    public function testADeliveredOrderGoesIntoTheCollection(): void
    {
        [$release] = $this->releasesWithStock(5);
        $order = $this->placeOrder($this->customer(), [self::idOf($release) => 1]);
        $this->apply($order, OrderTransition::PAY, OrderTransition::SHIP, OrderTransition::DELIVER);

        $crawler = $this->detail($order);
        $this->client->submit($crawler->filter('.kb-order-action form')->form());

        self::assertResponseRedirects($this->detailUri($order), Response::HTTP_SEE_OTHER);
        self::assertTrue($this->inCollection($release));
        $this->client->followRedirect();
        self::assertSelectorTextContains('.kb-order-action', 'Déjà dans ta collection');
    }

    public function testOnlyADeliveredOrderGoesIntoTheCollection(): void
    {
        [$release] = $this->releasesWithStock(5);
        $delivered = $this->placeOrder($this->customer(), [self::idOf($release) => 1]);
        $this->apply($delivered, OrderTransition::PAY, OrderTransition::SHIP, OrderTransition::DELIVER);
        $shipped = $this->placeOrder($this->customer(), [self::idOf($release) => 1]);
        $this->apply($shipped, OrderTransition::PAY, OrderTransition::SHIP);
        $token = $this->detail($delivered)->filter('.kb-order-action form input[name="_token"]')->attr('value');

        $this->client->request('POST', $this->detailUri($shipped).'/collection', ['_token' => $token]);

        self::assertResponseRedirects($this->detailUri($shipped), Response::HTTP_SEE_OTHER);
        self::assertFalse($this->inCollection($release));
    }

    public function testAnotherCustomersOrderCannotBeReordered(): void
    {
        [$release] = $this->releasesWithStock(5);
        $mine = $this->placeOrder($this->customer(), [self::idOf($release) => 1]);
        $this->apply($mine, OrderTransition::CANCEL);
        $theirs = $this->placeOrder($this->user(self::OTHER_CUSTOMER), [self::idOf($release) => 1]);
        $token = $this->detail($mine)->filter('.kb-order-again form input[name="_token"]')->attr('value');

        $this->client->request('POST', $this->detailUri($theirs).'/panier', ['_token' => $token]);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAnotherCustomersOrderCannotBeCollected(): void
    {
        [$release] = $this->releasesWithStock(5);
        $mine = $this->placeOrder($this->customer(), [self::idOf($release) => 1]);
        $this->apply($mine, OrderTransition::PAY, OrderTransition::SHIP, OrderTransition::DELIVER);
        $theirs = $this->placeOrder($this->user(self::OTHER_CUSTOMER), [self::idOf($release) => 1]);
        $this->apply($theirs, OrderTransition::PAY, OrderTransition::SHIP, OrderTransition::DELIVER);
        $token = $this->detail($mine)->filter('.kb-order-action form input[name="_token"]')->attr('value');

        $this->client->request('POST', $this->detailUri($theirs).'/collection', ['_token' => $token]);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    private function customer(): User
    {
        return $this->user(self::CUSTOMER);
    }

    private function orders(string $page = ''): Crawler
    {
        $this->client->loginUser($this->customer());
        $crawler = $this->client->request('GET', '/mon-compte/mes-commandes'.$page);
        self::assertResponseIsSuccessful();

        return $crawler;
    }

    private function detail(Order $order): Crawler
    {
        $this->client->loginUser($this->customer());
        $crawler = $this->client->request('GET', $this->detailUri($order));
        self::assertResponseIsSuccessful();

        return $crawler;
    }

    private function detailUri(Order $order): string
    {
        return '/mon-compte/mes-commandes/'.$order->getReference();
    }

    /**
     * @return list<string>
     */
    private function references(Crawler $crawler, string $group): array
    {
        return $crawler->filter($group.' tbody .kb-orders__ref')->each(static fn (Crawler $cell): string => trim($cell->text()));
    }

    private function apply(Order $order, OrderTransition ...$transitions): void
    {
        foreach ($transitions as $transition) {
            $this->bus()->dispatch(new ApplyOrderTransition((int) $order->getId(), $transition));
        }
    }

    /**
     * Goes through the cart and the delivery step, as CheckoutTest does.
     */
    private function checkout(Article $release): Order
    {
        $this->client->loginUser($this->customer());
        $crawler = $this->client->request('GET', \sprintf('/catalog/%s/%s', $release->getSupportType()?->value, $release->getSlug()));
        $this->client->submit($crawler->filter('form[name="add_to_cart_with_quantity"]')->form());
        $crawler = $this->client->request('GET', '/order/delivery');

        $form = $crawler->filter('form')->form();
        foreach (['deliveryAddress', 'transporter', 'paymentMethod'] as $field) {
            $choice = $form[$form->getName().'['.$field.']'];
            self::assertInstanceOf(\Symfony\Component\DomCrawler\Field\ChoiceFormField::class, $choice);
            $choice->select($choice->availableOptionValues()[0]);
        }
        $this->client->submit($form);

        $order = $this->entityManager()->getRepository(Order::class)->findOneBy([], ['id' => 'DESC']);
        self::assertInstanceOf(Order::class, $order);

        return $order;
    }

    private function inCollection(Article $article): bool
    {
        foreach (self::service(UserCollectionItemsRepository::class)->allOfUser($this->customer()) as $item) {
            if ($item->getArticle()?->getId() === $article->getId()) {
                return true;
            }
        }

        return false;
    }

    private function euros(int $cents): string
    {
        return (string) (new \NumberFormatter('fr', \NumberFormatter::CURRENCY))->formatCurrency($cents / 100, 'EUR');
    }
}
