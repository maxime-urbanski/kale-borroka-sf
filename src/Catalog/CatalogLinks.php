<?php

declare(strict_types=1);

namespace App\Catalog;

use App\Entity\Artist;
use App\Entity\Label;
use App\Entity\Style;
use App\Enum\ItemCondition;
use App\Enum\SupportType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * URLs of the catalogue page for a set of filters: the section in the path, the filters in
 * the query string by slug, the page last (none for the first one).
 */
final readonly class CatalogLinks
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function url(CatalogFilters $filters, ?int $page = null): string
    {
        $parameters = $this->query($filters);
        if (null !== $page && $page > 1) {
            $parameters['page'] = 'page-'.$page;
        }

        if (null !== $filters->section) {
            return $this->urlGenerator->generate('app_catalog_list', ['support' => $filters->section->value, ...$parameters]);
        }

        return $this->urlGenerator->generate('app_catalog', $parameters);
    }

    /**
     * The live count of « Voir N résultats », for the section of the page.
     */
    public function countUrl(?SupportType $section): string
    {
        return $this->urlGenerator->generate('app_catalog_count', null !== $section ? ['support' => $section->value] : []);
    }

    /**
     * The filters as query string parameters, defaults left out.
     *
     * @return array<string, list<string>|string|int>
     */
    public function query(CatalogFilters $filters): array
    {
        return array_filter([
            CatalogFacet::SUPPORTS->value => null === $filters->section ? array_map(static fn (SupportType $section): string => $section->value, $filters->supports) : [],
            CatalogFacet::STYLES->value => array_map(static fn (Style $style): string => (string) $style->getSlug(), $filters->styles),
            CatalogFacet::LABELS->value => array_map(static fn (Label $label): string => (string) $label->getSlug(), $filters->labels),
            CatalogFacet::ARTISTS->value => array_map(static fn (Artist $artist): string => (string) $artist->getSlug(), $filters->artists),
            CatalogFacet::CONDITIONS->value => array_map(static fn (ItemCondition $condition): string => $condition->value, $filters->conditions),
            CatalogFacet::KBR_PRODUCTION->value => $filters->kbrProduction ? 1 : 0,
            CatalogFacet::HIDE_SOLD_OUT->value => $filters->hideSoldOut ? 1 : 0,
            'sort' => CatalogSort::NEW === $filters->sort ? '' : $filters->sort->value,
        ], static fn (array|string|int $value): bool => [] !== $value && '' !== $value && 0 !== $value);
    }

    /**
     * Every active filter, each with the page without it.
     *
     * @return list<CatalogPill>
     */
    public function pills(CatalogFilters $filters): array
    {
        $pills = [];
        $add = function (CatalogFacet $group, string $label, callable $remove) use ($filters, &$pills): void {
            $without = clone $filters;
            $remove($without);
            $pills[] = new CatalogPill($group, $label, $without, $this->url($without));
        };

        if (null === $filters->section) {
            foreach ($filters->supports as $section) {
                $add(CatalogFacet::SUPPORTS, $section->label(), static fn (CatalogFilters $f) => $f->supports = self::without($f->supports, $section));
            }
        }
        foreach ($filters->styles as $style) {
            $add(CatalogFacet::STYLES, (string) $style->getName(), static fn (CatalogFilters $f) => $f->styles = self::without($f->styles, $style));
        }
        foreach ($filters->labels as $label) {
            $add(CatalogFacet::LABELS, (string) $label->getName(), static fn (CatalogFilters $f) => $f->labels = self::without($f->labels, $label));
        }
        foreach ($filters->artists as $artist) {
            $add(CatalogFacet::ARTISTS, (string) $artist->getName(), static fn (CatalogFilters $f) => $f->artists = self::without($f->artists, $artist));
        }
        foreach ($filters->conditions as $condition) {
            $add(CatalogFacet::CONDITIONS, $condition->label(), static fn (CatalogFilters $f) => $f->conditions = self::without($f->conditions, $condition));
        }
        if ($filters->kbrProduction) {
            $add(CatalogFacet::KBR_PRODUCTION, CatalogFacet::KBR_PRODUCTION->label(), static fn (CatalogFilters $f) => $f->kbrProduction = false);
        }
        if ($filters->hideSoldOut) {
            $add(CatalogFacet::HIDE_SOLD_OUT, CatalogFacet::HIDE_SOLD_OUT->label(), static fn (CatalogFilters $f) => $f->hideSoldOut = false);
        }

        return $pills;
    }

    /**
     * « Tout effacer »: the section kept, every filter dropped.
     */
    public function cleared(CatalogFilters $filters): string
    {
        return $this->url(CatalogFilters::of($filters->section));
    }

    /**
     * @template T
     *
     * @param list<T> $values
     * @param T       $value
     *
     * @return list<T>
     */
    private static function without(array $values, mixed $value): array
    {
        return array_values(array_filter($values, static fn (mixed $kept): bool => $kept !== $value));
    }
}
