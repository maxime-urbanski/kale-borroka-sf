<?php

declare(strict_types=1);

namespace App\Tests\Controller\User;

use App\Account\AddressBookPageInterface;
use App\Entity\Address;
use App\Repository\AddressRepository;
use App\Tests\Order\OrderTestTrait;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Form;

/**
 * Mes adresses (maquette 13): the cards, the form opened under the grid, the default address
 * and the five-address cap.
 */
class AddressBookTest extends WebTestCase
{
    use OrderTestTrait;

    private KernelBrowser $client;

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

    public function testTheDefaultAddressComesFirstWithItsBadge(): void
    {
        $this->addAddress('Le local', main: false);
        $this->addAddress('Chez Ama', main: false)->setIsMainAddress(true);
        $this->mainAddress()->setIsMainAddress(false);
        $this->entityManager()->flush();

        $crawler = $this->client->request('GET', '/mon-compte/mes-adresses');

        self::assertResponseIsSuccessful();
        $cards = $crawler->filter('.kb-address');
        self::assertCount(4, $cards);
        self::assertSame('Chez Ama', trim($cards->first()->filter('.kb-address__name')->text()));
        self::assertSelectorCount(1, '.kb-address--default');
        self::assertSelectorTextContains('.kb-address--default', 'Livraison par défaut');
        self::assertSelectorTextContains('.kb-account__head', '4 enregistrées · 5 max');
        self::assertSelectorNotExists('form[name="user_account_address_form"]', 'no form until one is asked for');
    }

    public function testEditingOpensTheFormUnderTheGrid(): void
    {
        $own = $this->mainAddress();

        $crawler = $this->client->request('GET', '/mon-compte/mes-adresses?modifier='.$own->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('#adresse-form h2', 'Modifier : '.$own->getName());
        $form = $crawler->filter('form[name="user_account_address_form"]');
        self::assertStringEndsWith('/mon-compte/mes-adresses/update/'.$own->getId().'#adresse-form', (string) $form->attr('action'));
        self::assertSame($own->getFirstname(), $form->filter('#user_account_address_form_firstname')->attr('value'));
    }

    public function testAnotherCustomersAddressIsNotOpened(): void
    {
        $victim = self::service(AddressRepository::class)->findOneBy(['users' => $this->user('client1@kaleborroka.test')]);
        self::assertInstanceOf(Address::class, $victim);

        $this->client->request('GET', '/mon-compte/mes-adresses?modifier='.$victim->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('form[name="user_account_address_form"]');
        self::assertAnySelectorTextNotContains('body', (string) $victim->getAddress());
    }

    public function testAMissingFieldIsShownOnTheFieldAndCreatesNothing(): void
    {
        $form = $this->addForm();
        $this->fill($form, ['name' => 'Le local', 'lastname' => '']);
        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('#user_account_address_form_lastname.is-invalid[aria-invalid="true"]');
        self::assertSelectorTextContains('#adresse-form', 'Nouvelle adresse');
        self::assertCount(2, $this->addressesOf('test@test.fr'));
    }

    public function testANewAddressTakesTheDefaultOnlyWhenAsked(): void
    {
        $main = $this->mainAddress();

        $form = $this->addForm();
        $this->fill($form, ['name' => 'Le local']);
        $this->client->submit($form);
        self::assertResponseRedirects('/mon-compte/mes-adresses');
        self::assertSame($main->getId(), $this->mainAddress()->getId(), 'unticked: the default stays');

        $form = $this->addForm();
        $this->fill($form, ['name' => 'Chez Ama']);
        $box = $form['user_account_address_form[makeDefault]'];
        self::assertInstanceOf(ChoiceFormField::class, $box);
        $box->tick();
        $this->client->submit($form);
        self::assertSame('Chez Ama', $this->mainAddress()->getName());
        self::assertCount(4, $this->addressesOf('test@test.fr'));
        $this->client->followRedirect();
        self::assertSelectorTextContains('.kb-address--default', 'Chez Ama');
    }

    public function testTheFirstAddressIsTheDefault(): void
    {
        $addresses = $this->addressesOf('test@test.fr');
        $this->user('test@test.fr')->setDefaultAddress(null);
        foreach ($addresses as $address) {
            $this->entityManager()->remove($address);
        }
        $this->entityManager()->flush();

        $crawler = $this->client->request('GET', '/mon-compte/mes-adresses');
        self::assertSelectorExists('#adresse-form', 'an empty address book opens the form');
        $form = $crawler->filter('form[name="user_account_address_form"]')->form();
        $this->fill($form, ['name' => 'Domicile']);
        $this->client->submit($form);

        self::assertSame('Domicile', $this->mainAddress()->getName());
    }

    public function testTheAddressBookHoldsFiveAddresses(): void
    {
        $form = $this->addForm();
        $saved = \count($this->addressesOf('test@test.fr'));
        for ($i = $saved + 1; $i <= AddressBookPageInterface::MAX_ADDRESSES; ++$i) {
            $this->addAddress('Adresse '.$i, main: false);
        }
        $this->entityManager()->flush();

        $this->client->request('GET', '/mon-compte/mes-adresses');
        self::assertSelectorNotExists('a[href$="?ajouter#adresse-form"]', 'no add tile once full');
        self::assertSelectorTextContains('.kb-account__head', '5 enregistrées · 5 max');

        $this->fill($form, ['name' => 'Une de trop']);
        $this->client->submit($form);

        self::assertResponseRedirects('/mon-compte/mes-adresses');
        $this->client->followRedirect();
        self::assertSelectorTextContains('body', 'Cinq adresses au plus');
        self::assertCount(AddressBookPageInterface::MAX_ADDRESSES, $this->addressesOf('test@test.fr'));
    }

    public function testAnAddressIsEditedWithItsRecipientAndPhone(): void
    {
        $own = $this->mainAddress();
        $crawler = $this->client->request('GET', '/mon-compte/mes-adresses?modifier='.$own->getId());
        $form = $crawler->filter('form[name="user_account_address_form"]')->form();
        $this->fill($form, ['firstname' => 'Maialen', 'lastname' => 'Etxeberria', 'telephone' => '+33 6 12 34 56 78']);
        $this->client->submit($form);

        self::assertResponseRedirects('/mon-compte/mes-adresses');
        $this->entityManager()->clear();
        $address = $this->mainAddress();
        self::assertSame('Maialen', $address->getFirstname());
        self::assertSame('+33 6 12 34 56 78', $address->getTelephone());
        // The order snapshot names whom to deliver and how to reach them.
        self::assertStringContainsString("Maialen Etxeberria\n", (string) $address);
        self::assertStringEndsWith("\n+33 6 12 34 56 78", (string) $address);
    }

    public function testAnEditedAddressKeepsItsFieldsWhenOneIsEmptied(): void
    {
        $own = $this->mainAddress();
        $crawler = $this->client->request('GET', '/mon-compte/mes-adresses?modifier='.$own->getId());
        $form = $crawler->filter('form[name="user_account_address_form"]')->form();
        $this->fill($form, ['lastname' => '', 'telephone' => 'abc']);
        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#adresse-form', 'Indique le nom de la personne qui reçoit le colis.');
        self::assertSelectorExists('#user_account_address_form_telephone.is-invalid');
        self::assertSelectorTextContains('#adresse-form h2', 'Modifier : '.$own->getName());
        self::assertSame($own->getLastname(), $this->mainAddress()->getLastname());
    }

    public function testAnotherAddressIsMadeTheDefault(): void
    {
        $other = $this->addAddress('Le local', main: false);
        $this->entityManager()->flush();

        $crawler = $this->client->request('GET', '/mon-compte/mes-adresses');
        $this->client->submit($this->buttonForm($crawler, '/mon-compte/mes-adresses/default/'.$other->getId()));

        self::assertResponseRedirects('/mon-compte/mes-adresses');
        self::assertSame($other->getId(), $this->mainAddress()->getId());
        self::assertCount(1, array_filter($this->addressesOf('test@test.fr'), static fn (Address $a): bool => true === $a->isIsMainAddress()));
    }

    public function testDeletingTheDefaultAddressHandsTheDefaultOn(): void
    {
        $main = $this->mainAddress();
        $this->addAddress('Le local', main: false);
        $this->entityManager()->flush();
        $others = array_values(array_filter($this->addressesOf('test@test.fr'), static fn (Address $a): bool => $a->getId() !== $main->getId()));
        self::assertCount(2, $others);

        $crawler = $this->client->request('GET', '/mon-compte/mes-adresses');
        $this->client->submit($this->buttonForm($crawler, '/mon-compte/mes-adresses/remove/'.$main->getId()));

        self::assertResponseRedirects('/mon-compte/mes-adresses');
        $this->entityManager()->clear();
        self::assertNull($this->entityManager()->find(Address::class, $main->getId()));
        self::assertSame($others[0]->getId(), $this->mainAddress()->getId(), 'the oldest one left');
    }

    public function testAnAddWaitsForTheOneInProgressOnTheSameAccount(): void
    {
        $form = $this->addForm();
        $this->fill($form, ['name' => 'Le local']);
        $user = $this->user('test@test.fr');

        // Another device in the middle of an add holds the account's row. NO KEY UPDATE, so that
        // only the add's own lock waits for it (the foreign key check of an INSERT does not).
        $connection = self::service(EntityManagerInterface::class)->getConnection();
        $other = DriverManager::getConnection($connection->getParams());
        $other->beginTransaction();
        $other->executeStatement('SELECT id FROM "user" WHERE id = ? FOR NO KEY UPDATE', [$user->getId()]);
        $this->client->catchExceptions(false);

        try {
            // This one would wait for its commit (a few milliseconds in real life): here it
            // gives up after 200ms, which shows it was waiting.
            $connection->executeStatement("SET LOCAL lock_timeout = '200ms'");
            $this->client->submit($form);
            self::fail('the add went through while another one held the account');
        } catch (DriverException $exception) {
            // 55P03, lock_not_available: it waited for the lock, rather than count and insert
            // next to the other add.
            self::assertSame('55P03', $exception->getSQLState());
        } finally {
            $other->rollBack();
            $other->close();
        }

        self::assertSame(2, (int) $connection->fetchOne('SELECT COUNT(*) FROM address WHERE users_id = ?', [$user->getId()]));
    }

    public function testTwoDefaultAddressesBreakNothing(): void
    {
        // What two parallel first addresses would leave behind.
        $this->addAddress('Le local', main: true);
        $this->entityManager()->flush();

        $this->client->request('GET', '/mon-compte');
        self::assertResponseIsSuccessful();
        $this->client->request('GET', '/mon-compte/mes-adresses');
        self::assertResponseIsSuccessful();
    }

    private function addForm(): Form
    {
        return $this->client->request('GET', '/mon-compte/mes-adresses?ajouter')
            ->filter('form[name="user_account_address_form"]')->form();
    }

    /**
     * A valid address, with the given fields changed.
     *
     * @param array<string, string> $values
     */
    private function fill(Form $form, array $values): void
    {
        $values += [
            'name' => 'Domicile', 'country' => 'France', 'firstname' => 'Maialen', 'lastname' => 'Etxeberria',
            'address' => '12 rue des Cordeliers', 'complement_address' => '', 'zipcode' => '64100', 'city' => 'Baiona',
            'telephone' => '',
        ];
        foreach ($values as $field => $value) {
            $form['user_account_address_form['.$field.']'] = $value;
        }
    }

    private function buttonForm(Crawler $crawler, string $action): Form
    {
        $form = $crawler->filter(\sprintf('form[action$="%s"]', $action));
        self::assertCount(1, $form, 'one button posts to '.$action);

        return $form->form();
    }

    private function addAddress(string $name, bool $main): Address
    {
        $address = (new Address())->setName($name)->setFirstname('Maialen')->setLastname('Etxeberria')
            ->setAddress('Kale Nagusia 8')->setZipcode('48003')->setCity('Bilbo')->setCountry('Espagne')
            ->setIsMainAddress($main)->setUsers($this->user('test@test.fr'));
        $this->entityManager()->persist($address);

        return $address;
    }

    private function mainAddress(): Address
    {
        $address = self::service(AddressRepository::class)->getDefaultUserAddress($this->user('test@test.fr'));
        self::assertInstanceOf(Address::class, $address);

        return $address;
    }

    /**
     * @return list<Address>
     */
    private function addressesOf(string $email): array
    {
        $this->entityManager()->clear();

        return array_values(self::service(AddressRepository::class)->getAllUserAddresses($this->user($email)));
    }
}
