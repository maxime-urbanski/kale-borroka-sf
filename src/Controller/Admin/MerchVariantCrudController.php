<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\MerchVariant;
use App\Enum\MerchSize;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * Entry form of the variants collection in MerchCrudController; the list only exists
 * to find a variant by SKU. Variants are created from their merch, never on their own.
 *
 * @extends AbstractCrudController<MerchVariant>
 */
class MerchVariantCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return MerchVariant::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Variante')
            ->setEntityLabelInPlural('Variantes')
            ->setSearchFields(['name', 'sku', 'gtin']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Nom')
            ->hideOnForm();
        yield ChoiceField::new('size', 'Taille')
            ->setChoices(MerchSize::cases())
            ->setFormTypeOption('choice_label', static fn (MerchSize $size): string => $size->label())
            ->setColumns(3);
        yield TextField::new('color', 'Couleur')
            ->setColumns(3);
        yield IntegerField::new('stock', 'Stock')
            ->setColumns(2);
        yield MoneyField::new('price', 'Prix')
            ->setCurrency('EUR')
            ->setColumns(2);
        yield TextField::new('sku', 'SKU')
            ->setRequired(false)
            ->setColumns(2);
        yield BooleanField::new('published', 'Publié');
    }
}
