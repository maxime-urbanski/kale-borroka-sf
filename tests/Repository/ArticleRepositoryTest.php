<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Article;
use App\Entity\Book;
use App\Entity\MerchVariant;
use App\Entity\Release;
use App\Enum\HomeShelf;
use App\Enum\ItemCondition;
use App\Enum\ReleaseFormat;
use App\Repository\ArticleRepository;
use App\Tests\DatabaseIsolationTrait;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The homepage's « Derniers articles », one shelf per chip.
 */
class ArticleRepositoryTest extends KernelTestCase
{
    use DatabaseIsolationTrait;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->beginIsolation();
    }

    protected function tearDown(): void
    {
        $this->endIsolation();
        parent::tearDown();
    }

    /**
     * @return iterable<string, array{HomeShelf, \Closure(Article): bool}>
     */
    public static function shelves(): iterable
    {
        $vinyls = [ReleaseFormat::VINYL_12, ReleaseFormat::VINYL_10, ReleaseFormat::VINYL_7];

        yield 'tout' => [HomeShelf::ALL, static fn (Article $article): bool => true];
        yield 'vinyles' => [HomeShelf::VINYL, static fn (Article $article): bool => $article instanceof Release && \in_array($article->getFormat(), $vinyls, true)];
        yield 'cd' => [HomeShelf::CD, static fn (Article $article): bool => $article instanceof Release && ReleaseFormat::CD === $article->getFormat()];
        yield 'k7' => [HomeShelf::TAPE, static fn (Article $article): bool => $article instanceof Release && ReleaseFormat::CASSETTE === $article->getFormat()];
        yield 'zines' => [HomeShelf::ZINE, static fn (Article $article): bool => $article instanceof Book];
    }

    /**
     * @param \Closure(Article): bool $belongs
     */
    #[DataProvider('shelves')]
    public function testEachShelfHoldsItsOwnPublishedArticles(HomeShelf $shelf, \Closure $belongs): void
    {
        $articles = $this->latest($shelf, 6);

        self::assertNotEmpty($articles, 'the fixtures fill every shelf but « Occasion »');
        self::assertLessThanOrEqual(6, \count($articles));

        foreach ($articles as $article) {
            self::assertTrue($article->isPublished(), (string) $article->getName());
            self::assertTrue($belongs($article), \sprintf('"%s" is not in « %s »', $article->getName(), $shelf->label()));
        }
    }

    public function testTheNewestComeFirst(): void
    {
        $oldest = $this->entityManager()->getRepository(Release::class)->findOneBy(['published' => true], ['id' => 'ASC']);
        self::assertNotNull($oldest);
        $oldest->setCreatedAt(new \DateTimeImmutable('+1 day'));
        $this->entityManager()->flush();

        self::assertSame($oldest->getId(), ($this->latest(HomeShelf::ALL, 6)[0] ?? null)?->getId());
    }

    /**
     * A t-shirt has no page of its own yet: its card would lead nowhere.
     */
    public function testNoMerchOnAnyShelf(): void
    {
        $merch = $this->entityManager()->getRepository(MerchVariant::class)->findOneBy(['published' => true]);
        self::assertNotNull($merch);
        $merch->setCreatedAt(new \DateTimeImmutable('+1 day'));
        $merch->setItemCondition(ItemCondition::USED);
        $this->entityManager()->flush();

        foreach (HomeShelf::cases() as $shelf) {
            foreach ($this->latest($shelf, 100) as $article) {
                self::assertNotInstanceOf(MerchVariant::class, $article, $shelf->label());
            }
        }
    }

    public function testTheUsedShelfHoldsSecondHandCopies(): void
    {
        self::assertSame([], $this->latest(HomeShelf::USED, 6));

        $copy = $this->entityManager()->getRepository(Release::class)->findOneBy(['published' => true]);
        self::assertNotNull($copy);
        $copy->setItemCondition(ItemCondition::USED);
        $this->entityManager()->flush();

        self::assertSame([$copy->getId()], array_map(static fn (Article $article): ?int => $article->getId(), $this->latest(HomeShelf::USED, 6)));
    }

    /**
     * @return list<Article>
     */
    private function latest(HomeShelf $shelf, int $limit): array
    {
        /* @var list<Article> */
        return self::service(ArticleRepository::class)->latest($shelf, $limit)->getResult();
    }

    private function entityManager(): EntityManagerInterface
    {
        return self::service(EntityManagerInterface::class);
    }
}
