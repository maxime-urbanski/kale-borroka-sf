<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Release;
use App\Enum\ItemCondition;
use App\Enum\ReleaseFormat;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * Pressings ("Exemplaires & Pressages"). Also used as the entry form of the releases
 * collection in AlbumCrudController, where the album field is implied.
 *
 * @extends AbstractCrudController<Release>
 */
class ReleaseCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Release::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Pressage')
            ->setEntityLabelInPlural('Exemplaires & pressages')
            ->setSearchFields(['name', 'sku', 'gtin', 'catalogNumber', 'album.name'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        $viewArticle = Action::new('view', 'Voir la page de l\'article')
            ->renderAsLink()
            ->linkToRoute('app_catalog_show', fn (Release $release) => [
                'support' => $release->getSupportType()?->value,
                'slug' => $release->getSlug(),
            ])
            ->displayIf(static fn (Release $release): bool => $release->isPublished())
            ->setHtmlAttributes(['target' => '_blank'])
            ->setCssClass('btn btn-success');

        return $actions
            ->add(Crud::PAGE_EDIT, $viewArticle);
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('album')
            ->autocomplete()
            ->setColumns(6)
            // Implied when the release is edited inside its album's form.
            ->setFormTypeOption('required', true);
        yield TextField::new('name', 'Nom de l\'article')
            ->setColumns(6);
        yield ChoiceField::new('format', 'Format')
            ->setChoices(ReleaseFormat::cases())
            ->setFormTypeOption('choice_label', static fn (ReleaseFormat $format): string => $format->label())
            ->setColumns(3);
        yield TextField::new('color', 'Couleur')
            ->setColumns(3);
        yield TextField::new('editionLabel', 'Édition')
            ->setColumns(3)
            ->hideOnIndex();
        yield IntegerField::new('limitedTo', 'Tirage limité à')
            ->setColumns(3)
            ->hideOnIndex();
        yield TextField::new('catalogNumber', 'N° de catalogue')
            ->setColumns(3)
            ->hideOnIndex();
        yield AssociationField::new('label', 'Label (distro)')
            ->setColumns(3)
            ->hideOnIndex();
        yield IntegerField::new('pressingYear', 'Année de pressage')
            ->setColumns(3)
            ->hideOnIndex();
        yield ChoiceField::new('itemCondition', 'État')
            ->setChoices(ItemCondition::cases())
            ->setFormTypeOption('choice_label', static fn (ItemCondition $condition): string => $condition->label())
            ->setColumns(3)
            ->hideOnIndex();
        yield TextField::new('sku', 'SKU')
            ->setHelp('Laisser vide pour le générer.')
            ->setRequired(false)
            ->setColumns(3);
        yield TextField::new('gtin', 'EAN / UPC')
            ->setColumns(3)
            ->hideOnIndex();
        yield IntegerField::new('stock', 'Stock')
            ->setColumns(3);
        yield MoneyField::new('price', 'Prix')
            ->setCurrency('EUR')
            ->setColumns(3);
        yield BooleanField::new('published', 'Publié');
    }
}
