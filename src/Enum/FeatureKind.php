<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * What the homepage feature (FeaturedRelease) says about its record: its stamp, its
 * default headline and its buy button.
 */
enum FeatureKind: string
{
    case PREORDER = 'preorder';
    case FAVORITE = 'favorite';
    case DISCOVER = 'discover';

    public function label(): string
    {
        return match ($this) {
            self::PREORDER => 'Prévente',
            self::FAVORITE => 'Coup de cœur',
            self::DISCOVER => 'À découvrir',
        };
    }

    /** Shown above the title when the feature has no headline of its own. */
    public function defaultHeadline(bool $kbrProduction): string
    {
        return match ($this) {
            self::PREORDER => $kbrProduction ? 'Prochaine sortie du label' : 'Bientôt dans la distro',
            self::FAVORITE => 'Le disque qui tourne en boucle au label',
            self::DISCOVER => $kbrProduction ? 'Une prod du label à découvrir' : 'Une pépite de la distro',
        };
    }

    public function callToAction(): string
    {
        return self::PREORDER === $this ? 'Précommander' : 'Ajouter au panier';
    }
}
