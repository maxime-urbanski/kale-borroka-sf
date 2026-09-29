<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Repository\UserRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * EasyAdmin type-checks fields at render time only (a TextField on an int throws), so
 * every list and form page is rendered once.
 */
class AdminSmokeTest extends WebTestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function pageProvider(): iterable
    {
        foreach (['release', 'book', 'merch', 'merch_variant', 'category', 'page', 'album', 'artist', 'label', 'support'] as $crud) {
            yield $crud.' index' => ['admin_'.$crud.'_index'];

            if ('merch_variant' !== $crud) {
                yield $crud.' new' => ['admin_'.$crud.'_new'];
            }
        }
    }

    #[DataProvider('pageProvider')]
    public function testPageRenders(string $route): void
    {
        $client = self::createClient();
        $admin = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'maxiloud@gmail.com']);
        self::assertNotNull($admin);
        $client->loginUser($admin);

        $client->request('GET', self::getContainer()->get('router')->generate($route));

        self::assertResponseIsSuccessful();
    }

    public function testEditPagesRenderForEveryArticleType(): void
    {
        $client = self::createClient();
        $container = self::getContainer();
        $client->loginUser($container->get(UserRepository::class)->findOneBy(['email' => 'maxiloud@gmail.com']));
        $doctrine = $container->get('doctrine');

        foreach ([
            'admin_release_edit' => \App\Entity\Release::class,
            'admin_book_edit' => \App\Entity\Book::class,
            'admin_merch_edit' => \App\Entity\Merch::class,
        ] as $route => $class) {
            $entity = $doctrine->getRepository($class)->findOneBy([]);
            self::assertNotNull($entity);

            $client->request('GET', $container->get('router')->generate($route, ['entityId' => $entity->getId()]));
            self::assertResponseIsSuccessful($route);
        }
    }
}
