<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\FeaturedRelease;
use App\Entity\Release;
use App\Enum\FeatureKind;
use App\Repository\FeaturedReleaseRepository;
use App\Tests\DatabaseIsolationTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The homepage shows a single feature: the latest online one, within its display dates,
 * whose release is on sale.
 */
class FeaturedReleaseRepositoryTest extends KernelTestCase
{
    use DatabaseIsolationTrait;

    private const string NOW = '2026-10-14 12:00';

    protected function setUp(): void
    {
        self::bootKernel();
        $this->beginIsolation();

        // Only the rows of each test compete.
        foreach ($this->repository()->findAll() as $feature) {
            $this->entityManager()->remove($feature);
        }
        $this->entityManager()->flush();
    }

    protected function tearDown(): void
    {
        $this->endIsolation();
        parent::tearDown();
    }

    public function testNothingWhenNoFeatureIsOnline(): void
    {
        $this->add(published: false);

        self::assertNull($this->current());
    }

    public function testTheDisplayDatesAreHonoured(): void
    {
        $this->add(from: '2026-10-15');
        $this->add(until: '2026-10-14 11:59');
        self::assertNull($this->current());

        $shown = $this->add(from: '2026-10-14 12:00', until: '2026-10-14 12:01');
        self::assertSame($shown->getId(), $this->current()?->getId());
    }

    public function testTheLatestScheduledWinsOverTheOlderOnes(): void
    {
        $this->add();
        $this->add(from: '2026-09-01');
        $latest = $this->add(from: '2026-10-01');
        $this->add(from: '2026-09-15');

        self::assertSame($latest->getId(), $this->current()?->getId());
    }

    public function testAnUndatedFeatureGivesWayToAScheduledOne(): void
    {
        $this->add();
        $scheduled = $this->add(from: '2026-01-01');

        self::assertSame($scheduled->getId(), $this->current()?->getId());
    }

    public function testAnUnpublishedReleaseIsNeverFeatured(): void
    {
        $draft = $this->entityManager()->getRepository(Release::class)->findOneBy(['published' => false]);
        self::assertNotNull($draft);

        $this->add(release: $draft);

        self::assertNull($this->current());
    }

    private function add(bool $published = true, ?string $from = null, ?string $until = null, ?Release $release = null): FeaturedRelease
    {
        $feature = (new FeaturedRelease())
            ->setKind(FeatureKind::PREORDER)
            ->setRelease($release ?? $this->entityManager()->getRepository(Release::class)->findOneBy(['published' => true]))
            ->setPublished($published)
            ->setDatePublished(null === $from ? null : new \DateTimeImmutable($from))
            ->setExpires(null === $until ? null : new \DateTimeImmutable($until));

        $this->entityManager()->persist($feature);
        $this->entityManager()->flush();

        return $feature;
    }

    /**
     * @phpstan-impure
     */
    private function current(): ?FeaturedRelease
    {
        return $this->repository()->findCurrent(new \DateTimeImmutable(self::NOW));
    }

    private function repository(): FeaturedReleaseRepository
    {
        return self::service(FeaturedReleaseRepository::class);
    }

    private function entityManager(): EntityManagerInterface
    {
        return self::service(EntityManagerInterface::class);
    }
}
