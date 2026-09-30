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

        foreach ($repository->getLastReleases()->getResult() as $release) {
            self::assertTrue($release->isPublished());
        }

        $names = array_map(static fn (Release $release): ?string => $release->getName(), $repository->filterReleaseQuery(new \App\Data\ArticleFilterData())->getResult());
        self::assertNotContains('Brouillon jamais publié', $names);
    }
}
