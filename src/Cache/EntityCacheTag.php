<?php

declare(strict_types=1);

namespace App\Cache;

use function Symfony\Component\String\u;

/**
 * Cache tag of an entity class: its short name in snake_case (`SocialNetwork` becomes
 * `social_network`). A fragment lists the tags of every entity it displays, e.g.
 * `{% cache 'footer' tags(['support', 'social_network', 'page']) %}`.
 */
final class EntityCacheTag
{
    /**
     * Tags used by a {% cache %} block. Only these are invalidated, so that the hot paths
     * (orders, wishlist, login) do not write to the pool for nothing. A template using a
     * tag missing here fails CachedTemplatesTest.
     */
    public const array FRAGMENT_TAGS = ['album', 'article', 'page', 'social_network', 'style', 'support'];

    /**
     * @param class-string $class
     */
    public static function forClass(string $class): string
    {
        return u(substr($class, (int) strrpos($class, '\\') + 1))->snake()->toString();
    }
}
