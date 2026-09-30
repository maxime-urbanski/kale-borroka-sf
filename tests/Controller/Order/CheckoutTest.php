<?php

declare(strict_types=1);

namespace App\Tests\Controller\Order;

use App\Entity\Order;
use App\Enum\OrderStatus;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;

class CheckoutTest extends WebTestCase
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

    public function testCartBecomesAPendingOrder(): void
    {
        [$release] = $this->releasesWithStock(4);
        $this->client->loginUser($this->user('test@test.fr'));

        $crawler = $this->client->request('GET', \sprintf('/catalog/%s/%s', $release->getSupportType()?->value, $release->getSlug()));
        $this->client->submit($crawler->filter('form[name="add_to_cart_with_quantity"]')->form([
            'add_to_cart_with_quantity[quantity]' => '2',
        ]));
        $crawler = $this->client->request('GET', '/order/delivery');
        self::assertResponseIsSuccessful();

        // Every choice list has a single pre-selectable value in the fixtures: take the first one.
        $form = $crawler->filter('form')->form();
        foreach (['deliveryAddress', 'transporter', 'paymentMethod'] as $field) {
            $name = $form->getName() ? $form->getName().'['.$field.']' : $field;
            $choice = $form[$name];
            self::assertInstanceOf(ChoiceFormField::class, $choice);
            $choice->select($choice->availableOptionValues()[0]);
        }
        $this->client->submit($form);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h3', 'Merci pour votre commande');

        $order = $this->entityManager()->getRepository(Order::class)->findOneBy([], ['id' => 'DESC']);
        self::assertNotNull($order);
        self::assertSame(OrderStatus::PENDING, $order->getStatus());
        $line = $order->getOrderDetails()->first();
        self::assertNotFalse($line);
        self::assertSame(2, $line->getQuantity());
        self::assertSame(4, $this->stockOf($release), 'stock is only taken on payment');
    }

    /**
     * FrankenPHP worker mode serves many requests with one kernel (disableReboot() here).
     * The address list used to be computed once per worker, from the first customer.
     */
    public function testEachCustomerOnlySeesTheirOwnAddresses(): void
    {
        foreach (['maxiloud@gmail.com', 'test@test.fr'] as $email) {
            $user = $this->user($email);
            $this->client->loginUser($user);
            $crawler = $this->client->request('GET', '/order/delivery');
            self::assertResponseIsSuccessful();

            $expected = $user->getAddresses()->map(fn ($address) => (string) $address->getId())->getValues();
            $offered = $crawler->filter('input[name$="[deliveryAddress]"], input[name="deliveryAddress"]')
                ->each(fn ($input) => $input->attr('value'));
            if ([] === $offered) {
                $offered = $crawler->filter('select[name$="deliveryAddress]"] option, select[name="deliveryAddress"] option')
                    ->each(fn ($option) => $option->attr('value'));
            }

            sort($expected);
            $offered = array_values(array_filter($offered));
            sort($offered);
            self::assertSame($expected, $offered, \sprintf('%s should be offered their own addresses only', $email));
        }
    }
}
