<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\MerchVariant;
use App\Enum\MerchSize;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * Entry form of the variants collection in MerchCrudController. Its own list only
 * exists to find a variant by SKU: variants are created from their merch.
 *
 * @extends AbstractArticleCrudController<MerchVariant>
 */
class MerchVariantCrudController extends AbstractArticleCrudController
{
    public static function getEntityFqcn(): string
    {
        return MerchVariant::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setEntityLabelInSingular('Variante')
            ->setEntityLabelInPlural('Variantes');
    }

    public function configureActions(Actions $actions): Actions
    {
        return parent::configureActions($actions)
            ->disable(Action::NEW, 'duplicate');
    }

    public function configureFields(string $pageName): iterable
    {
        foreach (parent::configureFields($pageName) as $field) {
            // The name is derived from the design, size and colour.
            if ($field instanceof TextField && 'name' === $field->getAsDto()->getProperty()) {
                $field->hideOnForm();
            }

            yield $field;
        }
    }

    protected function configureSpecificFields(string $pageName): iterable
    {
        yield ChoiceField::new('size', 'Taille')
            ->setChoices(MerchSize::cases())
            ->setFormTypeOption('choice_label', static fn (MerchSize $size): string => $size->label())
            ->setColumns(3);
        yield TextField::new('color', 'Couleur')
            ->setColumns(3);
    }
}
