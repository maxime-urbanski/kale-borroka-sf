<?php

declare(strict_types=1);

namespace App\Tests\Cache;

use App\Cache\EntityCacheTag;
use App\Entity\Release;
use App\Entity\SocialNetwork;
use PHPUnit\Framework\TestCase;

class EntityCacheTagTest extends TestCase
{
    public function testTagIsTheSnakeCaseShortName(): void
    {
        self::assertSame('release', EntityCacheTag::forClass(Release::class));
        self::assertSame('social_network', EntityCacheTag::forClass(SocialNetwork::class));
    }
}
