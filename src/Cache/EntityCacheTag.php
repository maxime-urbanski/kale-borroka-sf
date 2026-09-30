<?php

declare(strict_types=1);

namespace App\Cache;

/**
 * Cache tag of an entity class: its short name in snake_case (`SocialNetwork` becomes
 * `social_network`). A fragment lists the tags of every entity it displays, e.g.
 * `{% cache 'footer' tags(['support', 'social_network', 'page']) %}`.
 */
final class EntityCacheTag
{
    /**
     * @param class-string $class
     */
    public static function forClass(string $class): string
    {
        $shortName = substr($class, (int) strrpos($class, '\\') + 1);

        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $shortName));
    }
}
