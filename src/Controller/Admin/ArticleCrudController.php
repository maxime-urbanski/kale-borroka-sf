<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Enum\ItemAvailability;
use App\Enum\ItemCondition;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;

/**
 * Article is the offer: price, stock and condition only. What the record *is* belongs to
 * Edition and Album.
 *
 * @extends AbstractCrudController<Article>
 */
class ArticleCrudController extends AbstractCrudController
{
    private const int LOW_STOCK_THRESHOLD = 3;

    public static function getEntityFqcn(): string
    {
        return Article::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Offre')
            ->setEntityLabelInPlural('Offres')
            ->setDefaultSort(['updatedAt' => 'DESC'])
            ->setSearchFields(['sku', 'gtin13', 'edition.name', 'edition.album.name'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        $viewArticle = Action::new('view', 'Voir la page de l\'article')
            ->renderAsLink()
            ->linkToRoute('app_catalog_show', fn (Article $article) => $article->getRouteParams())
            ->setHtmlAttributes(['target' => '_blank'])
            ->setCssClass('btn btn-success');

        return $actions
            ->add(Crud::PAGE_EDIT, $viewArticle);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('edition', 'Édition'))
            ->add(ChoiceFilter::new('condition', 'État')
                ->setChoices($this->enumChoices(ItemCondition::cases())))
            ->add(ChoiceFilter::new('availability', 'Disponibilité')
                ->setChoices($this->enumChoices(ItemAvailability::cases())))
            ->add(NumericFilter::new('quantity', 'Stock'))
            ->add(NumericFilter::new('price', 'Prix'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('edition', 'Édition')
            ->autocomplete()
            ->setColumns(6);
        yield ChoiceField::new('condition', 'État')
            ->setChoices($this->enumChoices(ItemCondition::cases()))
            ->setColumns(3);
        yield ChoiceField::new('availability', 'Disponibilité')
            ->setChoices($this->enumChoices(ItemAvailability::cases()))
            ->setColumns(3);
        yield MoneyField::new('price', 'Prix')
            ->setCurrency('EUR')
            ->setColumns(3);
        yield IntegerField::new('quantity', 'Quantité disponible')
            // Plain text rather than markup: EasyAdmin escapes formatted values, and a
            // marker is enough to spot what needs restocking when scanning the list.
            ->formatValue(static fn (?int $value): string => match (true) {
                null === $value, $value <= 0 => '0 — rupture',
                $value <= self::LOW_STOCK_THRESHOLD => $value.' — stock faible',
                default => (string) $value,
            })
            ->setColumns(3);
        yield IntegerField::new('weight', 'Poids (g)')
            ->setHelp('Utilisé pour le calcul des frais de port.')
            ->hideOnIndex()
            ->setColumns(3);
        yield DateField::new('availableFrom', 'Disponible à partir du')
            ->setHelp('Pour les précommandes.')
            ->hideOnIndex()
            ->setColumns(3);
        yield TextField::new('sku', 'Référence interne')
            ->hideOnIndex()
            ->setColumns(6);
        yield TextField::new('gtin13', 'Code-barres (EAN-13)')
            ->hideOnIndex()
            ->setColumns(6);
        yield TextareaField::new('description', 'Description')
            ->hideOnIndex()
            ->setColumns(12);
        yield DateTimeField::new('createdAt', 'Ajouté le')
            ->setTimezone('Europe/Paris')
            ->onlyOnDetail();
        yield DateTimeField::new('updatedAt', 'Modifié le')
            ->setTimezone('Europe/Paris')
            ->onlyOnDetail();
    }

    /**
     * @param array<int, ItemCondition|ItemAvailability> $cases
     *
     * @return array<string, ItemCondition|ItemAvailability>
     */
    private function enumChoices(array $cases): array
    {
        $choices = [];

        foreach ($cases as $case) {
            $choices[$case->label()] = $case;
        }

        return $choices;
    }
}
