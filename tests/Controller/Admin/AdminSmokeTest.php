<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Repository\UserRepository;
use App\Tests\ServiceTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * EasyAdmin type-checks fields at render time only (a TextField on an int throws), so
 * every list and form page is rendered once.
 */
class AdminSmokeTest extends WebTestCase
{
    use ServiceTrait;

    /**
     * @return iterable<string, array{string}>
     */
    public static function pageProvider(): iterable
    {
        foreach (['release', 'book', 'merch', 'merch_variant', 'category', 'page', 'album', 'artist', 'label', 'support', 'order', 'user', 'song', 'style', 'payment', 'transporter', 'image', 'social_network', 'expense', 'event_sale'] as $crud) {
            yield $crud.' index' => ['admin_'.$crud.'_index'];

            if (!\in_array($crud, ['merch_variant', 'order', 'user'], true)) {
                yield $crud.' new' => ['admin_'.$crud.'_new'];
            }
        }
    }

    #[DataProvider('pageProvider')]
    public function testPageRenders(string $route): void
    {
        $client = self::createClient();
        $admin = self::service(UserRepository::class)->findOneBy(['email' => 'maxiloud@gmail.com']);
        self::assertNotNull($admin);
        $client->loginUser($admin);

        $client->request('GET', self::service(\Symfony\Component\Routing\RouterInterface::class, 'router')->generate($route));

        self::assertResponseIsSuccessful();
    }

    public function testEditPagesRenderForEveryArticleType(): void
    {
        $client = self::createClient();
        $container = self::getContainer();
        $admin = self::service(UserRepository::class)->findOneBy(['email' => 'maxiloud@gmail.com']);
        self::assertNotNull($admin);
        $client->loginUser($admin);
        $doctrine = self::service(\Doctrine\Persistence\ManagerRegistry::class, 'doctrine');

        foreach ([
            'admin_release_edit' => \App\Entity\Release::class,
            'admin_book_edit' => \App\Entity\Book::class,
            'admin_merch_edit' => \App\Entity\Merch::class,
            'admin_album_edit' => \App\Entity\Album::class,
            'admin_user_edit' => \App\Entity\User::class,
            'admin_artist_edit' => \App\Entity\Artist::class,
            'admin_label_edit' => \App\Entity\Label::class,
        ] as $route => $class) {
            $entity = $doctrine->getRepository($class)->findOneBy([]);
            self::assertNotNull($entity);

            $client->request('GET', self::service(\Symfony\Component\Routing\RouterInterface::class, 'router')->generate($route, ['entityId' => $entity->getId()]));
            self::assertResponseIsSuccessful($route);

            // Detail pages have their own layout (tabs, detail-only fields).
            $detail = str_replace('_edit', '_detail', $route);
            $client->request('GET', self::service(\Symfony\Component\Routing\RouterInterface::class, 'router')->generate($detail, ['entityId' => $entity->getId()]));
            self::assertResponseIsSuccessful($detail);
        }
    }
}
