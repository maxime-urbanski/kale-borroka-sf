<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Release;
use App\Enum\SupportType;
use App\Repository\ReleaseRepository;
use App\Tests\ServiceTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ReleaseRepositoryTest extends KernelTestCase
{
    use ServiceTrait;

    /**
     * SupportType::forRelease() builds URLs, ReleaseRepository::applySupports() builds the
     * section listings: a release must always be listed under the section of its own URL.
     */
    public function testEveryReleaseIsListedUnderItsCanonicalSection(): void
    {
        $repository = self::service(ReleaseRepository::class);

        /** @var Release[] $releases */
        $releases = $repository->findBy(['published' => true]);
        self::assertNotEmpty($releases);

        foreach (SupportType::cases() as $supportType) {
            $listed = $repository->applySupports(
                $repository->createQueryBuilder('release')->innerJoin('release.album', 'album'),
                [$supportType],
            )->getQuery()->getResult();

            foreach ($releases as $release) {
                if ($release->getSupportType() === $supportType) {
                    self::assertContains(
                        $release,
                        $listed,
                        \sprintf('"%s" has a /catalog/%s URL but is not listed there', $release->getName(), $supportType->value),
                    );
                }
            }
        }
    }

    public function testUnpublishedReleasesAreNotListed(): void
    {
        $repository = self::service(ReleaseRepository::class);

        $names = array_map(static fn (Release $release): ?string => $release->getName(), $repository->filterReleaseQuery(new \App\Data\ArticleFilterData())->getResult());
        self::assertNotContains('Brouillon jamais publié', $names);
    }

    /**
     * The homepage's « Dernières prod »: KBR productions only, newest production number
     * first, one pressing per album.
     */
    public function testLatestProductionsAreTheNewestKbrNumbersOnePressingEach(): void
    {
        $productions = self::service(ReleaseRepository::class)->latestProductions(4)->getResult();

        self::assertCount(4, $productions);
        self::assertSame(['KBR#010', 'KBR#009', 'KBR#008', 'KBR#007'], array_map(
            static fn (Release $release): ?string => $release->getAlbum()?->getKbrProductionId(),
            $productions,
        ));

        foreach ($productions as $release) {
            self::assertTrue($release->isPublished());
        }
    }

    public function testLatestProductionsShowAnAlbumOnce(): void
    {
        $repository = self::service(ReleaseRepository::class);
        $newest = $repository->latestProductions(1)->getResult()[0] ?? null;
        self::assertInstanceOf(Release::class, $newest);

        $entityManager = self::service(\Doctrine\ORM\EntityManagerInterface::class);
        $entityManager->getConnection()->beginTransaction();

        try {
            $pressing = (clone $newest)->setName('Second pressage')->setPublished(true);
            $entityManager->persist($pressing);
            $entityManager->flush();

            $albums = array_map(
                static fn (Release $release): ?int => $release->getAlbum()?->getId(),
                $repository->latestProductions(4)->getResult(),
            );
            self::assertSame(array_unique($albums), $albums);
        } finally {
            $entityManager->getConnection()->rollBack();
        }
    }

    public function testProductionNumbersAreSortedAsNumbers(): void
    {
        $repository = self::service(ReleaseRepository::class);
        $entityManager = self::service(\Doctrine\ORM\EntityManagerInterface::class);
        $entityManager->getConnection()->beginTransaction();

        try {
            $oldest = $entityManager->getRepository(\App\Entity\Album::class)->findOneBy(['kbrProductionId' => 'KBR#001']);
            self::assertNotNull($oldest);
            $oldest->setKbrProductionId('KBR#1000');
            $entityManager->getRepository(\App\Entity\Album::class)->findOneBy(['kbrProductionId' => 'KBR#002'])?->setKbrProductionId('KBR#999');
            $entityManager->flush();

            self::assertSame('KBR#1000', ($repository->latestProductions(1)->getResult()[0] ?? null)?->getAlbum()?->getKbrProductionId());
        } finally {
            $entityManager->getConnection()->rollBack();
        }
    }
}
