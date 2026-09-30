<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Admin\Query\FundsBalance;
use App\Admin\Query\FundsSummary;
use App\Entity\EventSale;
use App\Entity\Expense;
use App\Entity\ShopSettings;
use App\Enum\ExpenseCategory;
use App\Enum\OrderTransition;
use App\Order\Command\ApplyOrderTransition;
use App\Repository\ShopSettingsRepository;
use App\Service\ShopSettingsProvider;
use App\Tests\Order\OrderTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Clock\NativeClock;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Available funds: opening balance + shop payments + event sales − label expenses.
 * Invoices really upload, into var/test-uploads/invoices, emptied after each test.
 */
class AdminFundsTest extends WebTestCase
{
    use OrderTestTrait;

    private const string PDF = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[]/Count 0>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";

    private ?KernelBrowser $client = null;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
        $this->beginIsolation();
        $this->client->loginUser($this->user('maxiloud@gmail.com'));
    }

    protected function tearDown(): void
    {
        $this->endIsolation();
        (new Filesystem())->remove(\dirname(__DIR__, 3).'/var/test-uploads');
        parent::tearDown();
    }

    public function testFundsAddShopPaymentsAndEventSalesAndTakeOffExpenses(): void
    {
        $before = $this->funds();

        [$release] = $this->releasesWithStock(5);
        $order = $this->placeOrder($this->user('test@test.fr'), [$release->getId() => 1]);
        $this->bus()->dispatch(new ApplyOrderTransition((int) $order->getId(), OrderTransition::PAY));
        $this->entityManager()->persist((new EventSale())->setName('Fest')->setStartTime(new \DateTimeImmutable())->setPrice(5000));
        $this->entityManager()->persist((new Expense())->setName('Timbres')->setCategory(ExpenseCategory::SHIPPING)->setPaymentDueDate(new \DateTimeImmutable())->setTotalPaymentDue(1200));
        $this->entityManager()->flush();

        $after = $this->funds();
        self::assertSame(1000, $after->shopRevenue - $before->shopRevenue);
        self::assertSame(5000, $after->eventSales - $before->eventSales);
        self::assertSame(1200, $after->expenses - $before->expenses);
        self::assertSame($before->available() + 1000 + 5000 - 1200, $after->available());

        $crawler = $this->client->request('GET', '/admin');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Fonds disponibles', $crawler->filter('[data-test="funds"]')->text());
    }

    public function testFutureExpensesAreUpcomingAndFutureEventSalesIgnored(): void
    {
        $before = $this->funds();
        $nextMonth = new \DateTimeImmutable('+30 days');
        $this->entityManager()->persist((new Expense())->setName('Pressage à payer')->setCategory(ExpenseCategory::PRODUCTION)->setPaymentDueDate($nextMonth)->setTotalPaymentDue(80000));
        $this->entityManager()->persist((new EventSale())->setName('Fest à venir')->setStartTime($nextMonth)->setPrice(5000));
        $this->entityManager()->flush();

        $after = $this->funds();
        self::assertSame($before->available(), $after->available());
        self::assertSame(80000, $after->upcomingExpenses - $before->upcomingExpenses);

        $crawler = $this->client->request('GET', '/admin');
        self::assertStringContainsString('Frais à venir', $crawler->filter('[data-test="funds-upcoming"]')->text());
    }

    public function testOpeningBalanceAndDateAreEditable(): void
    {
        $settings = $this->settings();
        $form = $this->client->request('GET', \sprintf('/admin/shop-settings/%d/edit', $settings->getId()))->filter('form[name="ShopSettings"]')->form();
        $values = $form->getPhpValues();
        $values['ShopSettings']['openingBalance'] = '2500,50';
        // Nothing has happened yet on a future date: the funds are the opening balance.
        $values['ShopSettings']['openingBalanceDate'] = (new \DateTimeImmutable('+1 day'))->format('Y-m-d');
        $this->client->request('POST', $form->getUri(), $values);
        self::assertResponseRedirects();

        $funds = $this->funds();
        self::assertSame(250050, $funds->openingBalance);
        self::assertSame(0, $funds->shopRevenue + $funds->eventSales + $funds->expenses);
        self::assertSame(250050, $funds->available());
    }

    public function testEventSaleIsEnteredFromTheBackOffice(): void
    {
        $form = $this->client->request('GET', '/admin/event-sale/new')->filter('form[name="EventSale"]')->form();
        $values = $form->getPhpValues();
        $values['EventSale'] = array_merge($values['EventSale'], [
            'startTime' => '2026-06-21',
            'name' => 'Fête de la musique',
            'location' => 'Bayonne',
            'price' => '342,50',
        ]);
        $this->client->request('POST', $form->getUri(), $values);
        self::assertResponseRedirects();

        $sale = $this->entityManager()->getRepository(EventSale::class)->findOneBy(['name' => 'Fête de la musique']);
        self::assertSame(34250, $sale?->getPrice());
        self::assertSame('2026-06-21', $sale->getStartTime()?->format('Y-m-d'));
    }

    public function testExpenseInvoiceIsPrivateAndOnlyServedToAdmins(): void
    {
        $form = $this->client->request('GET', '/admin/expense/new')->filter('form[name="Expense"]')->form();
        $values = $form->getPhpValues();
        $values['Expense'] = array_merge($values['Expense'], [
            'paymentDueDate' => '2026-09-01',
            'name' => 'Sérigraphie tee Kale Borroka',
            'category' => 'merch',
            'provider' => 'Atelier',
            'totalPaymentDue' => '420',
        ]);
        $this->client->request('POST', $form->getUri(), $values, ['Expense' => ['invoiceFile' => ['file' => $this->pdf('facture-tshirts.pdf')]]]);
        self::assertResponseRedirects(null, null, 'the expense form should be valid');

        $expense = $this->entityManager()->getRepository(Expense::class)->findOneBy(['name' => 'Sérigraphie tee Kale Borroka']);
        self::assertInstanceOf(Expense::class, $expense);
        self::assertSame(42000, $expense->getTotalPaymentDue());
        self::assertSame(ExpenseCategory::MERCH, $expense->getCategory());
        self::assertSame('facture-tshirts.pdf', $expense->getInvoiceOriginalName());
        self::assertFileExists(\dirname(__DIR__, 3).'/var/test-uploads/invoices/'.$expense->getInvoiceName());

        $crawler = $this->client->request('GET', \sprintf('/admin/expense/%d', $expense->getId()));
        self::assertStringContainsString('Merch & textile', $crawler->filter('body')->text());
        self::assertCount(1, $crawler->filter(\sprintf('a[href$="/admin/expense/%d/invoice"]', $expense->getId())));

        $this->client->request('GET', \sprintf('/admin/expense/%d/invoice', $expense->getId()));
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/pdf');
        self::assertStringContainsString('facture-tshirts.pdf', (string) $this->client->getResponse()->headers->get('Content-Disposition'));

        // A client file name with characters Content-Disposition rejects still downloads.
        $expense->setInvoiceOriginalName('facture 50% \\ acompte.pdf');
        $this->entityManager()->flush();
        $this->client->request('GET', \sprintf('/admin/expense/%d/invoice', $expense->getId()));
        self::assertResponseIsSuccessful();

        // A customer is refused, an anonymous visitor sent to the login form.
        $this->client->loginUser($this->user('test@test.fr'));
        $this->client->request('GET', \sprintf('/admin/expense/%d/invoice', $expense->getId()));
        self::assertResponseStatusCodeSame(403);

        $this->client->getCookieJar()->clear();
        $this->client->request('GET', \sprintf('/admin/expense/%d/invoice', $expense->getId()));
        self::assertResponseRedirects('/login');
    }

    public function testExpenseRejectsNonInvoiceFiles(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'kbr');
        file_put_contents($path, '<?php echo "no";');

        $form = $this->client->request('GET', '/admin/expense/new')->filter('form[name="Expense"]')->form();
        $values = $form->getPhpValues();
        $values['Expense'] = array_merge($values['Expense'], ['paymentDueDate' => '2026-09-01', 'name' => 'Piège', 'category' => 'other', 'totalPaymentDue' => '10']);
        $this->client->request('POST', $form->getUri(), $values, ['Expense' => ['invoiceFile' => ['file' => new UploadedFile($path, 'facture.pdf', 'application/pdf', null, true)]]]);

        self::assertResponseStatusCodeSame(422);
        self::assertNull($this->entityManager()->getRepository(Expense::class)->findOneBy(['name' => 'Piège']));
    }

    private function funds(): FundsSummary
    {
        $entityManager = $this->entityManager();

        return (new FundsBalance($entityManager, new ShopSettingsProvider(self::getContainer()->get(ShopSettingsRepository::class)), new NativeClock()))->summary();
    }

    private function settings(): ShopSettings
    {
        $settings = self::getContainer()->get(ShopSettingsRepository::class)->findOneBy([]);
        self::assertInstanceOf(ShopSettings::class, $settings);

        return $settings;
    }

    private function pdf(string $name): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'kbr');
        file_put_contents($path, self::PDF);

        return new UploadedFile($path, $name, 'application/pdf', null, true);
    }
}
