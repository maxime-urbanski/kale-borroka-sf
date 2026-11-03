<?php

declare(strict_types=1);

namespace App\Catalog;

use App\Entity\Artist;
use App\Entity\Label;
use App\Entity\Style;
use App\Enum\ItemCondition;
use App\Enum\SupportType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * The filter groups of the catalogue page and the count next to each value: the number of
 * results ticking it would give, every other group applied (so a value of a group already
 * chosen counts as if it were added). One aggregate query per group.
 *
 * Shown: every section; the styles, labels and artists that have something to show, plus
 * the chosen ones (at 0, to untick them); the condition group only when something is not
 * new, or a condition is chosen.
 */
final readonly class FacetCounter
{
    private const int STYLES_VISIBLE = 8;
    private const int LABELS_VISIBLE = 8;
    private const int ARTISTS_VISIBLE = 6;

    public function __construct(
        private CatalogQuery $catalogQuery,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<string, Facet> by CatalogFacet value, in the order of the page
     */
    public function facets(CatalogFilters $filters): array
    {
        $facets = [];

        if (null === $filters->section) {
            $facets[CatalogFacet::SUPPORTS->value] = $this->supports($filters);
        }

        $facets[CatalogFacet::STYLES->value] = $this->styles($filters);
        $facets[CatalogFacet::LABELS->value] = $this->labels($filters);
        $facets[CatalogFacet::ARTISTS->value] = $this->artists($filters);

        $conditions = $this->conditions($filters);
        if (null !== $conditions) {
            $facets[CatalogFacet::CONDITIONS->value] = $conditions;
        }

        return $facets;
    }

    private function supports(CatalogFilters $filters): Facet
    {
        $query = $this->catalogQuery->filtered($filters, CatalogFacet::SUPPORTS);
        foreach (SupportType::cases() as $section) {
            $query->addSelect(\sprintf('SUM(CASE WHEN %s THEN 1 ELSE 0 END) AS %s', $this->catalogQuery->inSection($query, $section), $section->value));
        }
        /** @var array<string, int|string|null> $counts */
        $counts = $query->getQuery()->getSingleResult();

        return new Facet(CatalogFacet::SUPPORTS, array_map(
            static fn (SupportType $section): FacetOption => new FacetOption($section->value, $section->label(), (int) ($counts[$section->value] ?? 0), \in_array($section, $filters->supports, true)),
            SupportType::cases(),
        ), \count(SupportType::cases()));
    }

    private function styles(CatalogFilters $filters): Facet
    {
        $counts = $this->countBy($filters, CatalogFacet::STYLES, 'al.styles');
        /** @var list<Style> $styles */
        $styles = $this->entityManager->getRepository(Style::class)->findBy([], ['id' => 'ASC']);

        // Only the styles there is something for, and the chosen ones (to untick them).
        $options = [];
        foreach ($styles as $style) {
            $selected = \in_array($style, $filters->styles, true);
            $count = $counts[(int) $style->getId()] ?? 0;
            if ($count > 0 || $selected) {
                $options[] = new FacetOption((string) $style->getSlug(), (string) $style->getName(), $count, $selected);
            }
        }

        return new Facet(CatalogFacet::STYLES, $options, self::STYLES_VISIBLE);
    }

    private function labels(CatalogFilters $filters): Facet
    {
        // A record's album labels, a fanzine's publisher: never both for one article.
        $counts = $this->countBy($filters, CatalogFacet::LABELS, 'al.labels');
        foreach ($this->countBy($filters, CatalogFacet::LABELS, 'b.publisher') as $id => $count) {
            $counts[$id] = ($counts[$id] ?? 0) + $count;
        }

        return new Facet(CatalogFacet::LABELS, $this->listed(Label::class, $counts, $filters->labels, byCount: true), self::LABELS_VISIBLE);
    }

    private function artists(CatalogFilters $filters): Facet
    {
        $counts = $this->countBy($filters, CatalogFacet::ARTISTS, 'al.artist');
        $options = $this->listed(Artist::class, $counts, $filters->artists, byCount: false);

        $search = mb_strtolower(trim((string) $filters->artistSearch));
        if ('' !== $search) {
            $options = array_values(array_filter($options, static fn (FacetOption $option): bool => $option->selected || str_contains(mb_strtolower($option->label), $search)));
        }

        return new Facet(CatalogFacet::ARTISTS, $options, self::ARTISTS_VISIBLE);
    }

    private function conditions(CatalogFilters $filters): ?Facet
    {
        $query = $this->catalogQuery->filtered($filters, CatalogFacet::CONDITIONS)
            ->select('a.itemCondition AS value', 'COUNT(a.id) AS n')
            ->groupBy('a.itemCondition');

        $counts = [];
        /** @var array{value: ItemCondition|string, n: int|string} $row */
        foreach ($query->getQuery()->getArrayResult() as $row) {
            $condition = $row['value'] instanceof ItemCondition ? $row['value'] : ItemCondition::from($row['value']);
            $counts[$condition->value] = (int) $row['n'];
        }

        $somethingNotNew = [] !== array_filter($counts, static fn (int $count, string $condition): bool => $count > 0 && ItemCondition::NEW->value !== $condition, \ARRAY_FILTER_USE_BOTH);
        if (!$somethingNotNew && [] === $filters->conditions) {
            return null;
        }

        return new Facet(CatalogFacet::CONDITIONS, array_map(
            static fn (ItemCondition $condition): FacetOption => new FacetOption($condition->value, $condition->label(), $counts[$condition->value] ?? 0, \in_array($condition, $filters->conditions, true)),
            ItemCondition::cases(),
        ), \count(ItemCondition::cases()));
    }

    /**
     * Number of results per value of a group, reached through $association.
     *
     * @return array<int, int> by id
     */
    private function countBy(CatalogFilters $filters, CatalogFacet $group, string $association): array
    {
        $query = $this->catalogQuery->filtered($filters, $group)
            ->innerJoin($association, 'facet')
            ->select('facet.id AS id', 'COUNT(a.id) AS n')
            ->groupBy('facet.id');

        $counts = [];
        /** @var array{id: int|string, n: int|string} $row */
        foreach ($query->getQuery()->getArrayResult() as $row) {
            $counts[(int) $row['id']] = (int) $row['n'];
        }

        return $counts;
    }

    /**
     * The values that have results, plus the chosen ones (at 0, so they can be unticked).
     *
     * @template T of Label|Artist
     *
     * @param class-string<T> $class
     * @param array<int, int> $counts
     * @param list<T>         $selected
     *
     * @return list<FacetOption>
     */
    private function listed(string $class, array $counts, array $selected, bool $byCount): array
    {
        $selectedIds = array_map(static fn (Label|Artist $entity): ?int => $entity->getId(), $selected);
        $ids = array_values(array_unique([...array_keys(array_filter($counts)), ...array_filter($selectedIds)]));
        if ([] === $ids) {
            return [];
        }

        /** @var list<T> $entities */
        $entities = $this->entityManager->getRepository($class)->findBy(['id' => $ids]);
        $options = array_map(
            static fn (Label|Artist $entity): FacetOption => new FacetOption((string) $entity->getSlug(), (string) $entity->getName(), $counts[(int) $entity->getId()] ?? 0, \in_array($entity->getId(), $selectedIds, true)),
            $entities,
        );

        usort($options, static fn (FacetOption $a, FacetOption $b): int => ($byCount ? $b->count <=> $a->count : 0) ?: strcasecmp($a->label, $b->label));

        return $options;
    }
}
