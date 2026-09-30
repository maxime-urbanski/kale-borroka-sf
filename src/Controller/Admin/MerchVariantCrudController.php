<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\MerchVariant;
use App\Enum\MerchSize;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * Entry form of the variants collection in MerchCrudController (page names below). Its
 * own list only exists to find a variant by SKU: variants are created from their merch.
 *
 * @extends AbstractArticleCrudController<MerchVariant>
 */
class MerchVariantCrudController extends AbstractArticleCrudController
{
    public const string PAGE_IN_MERCH_NEW = 'merch_variant_new';
    public const string PAGE_IN_MERCH_EDIT = 'merch_variant_edit';

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

    protected function isEmbedded(string $pageName): bool
    {
        return \in_array($pageName, [self::PAGE_IN_MERCH_NEW, self::PAGE_IN_MERCH_EDIT], true);
    }

    protected function fallbackPicturesLabel(): string
    {
        return 'Sans photo propre, les visuels du merch sont affichés.';
    }

    protected function configureSpecificFields(string $pageName): iterable
    {
        yield FormField::addFieldset('Variante', 'fa fa-shirt');
        // Derived from the design, size and colour.
        yield $this->nameField()->hideOnForm();
        yield $this->sizeField()->setColumns(3);
        yield $this->colorField()->setColumns(3);
    }

    /**
     * One row per size in the merch form.
     */
    protected function configureEmbeddedFields(string $pageName): iterable
    {
        yield $this->sizeField()->setColumns(2);
        yield $this->colorField()->setColumns(3);
        yield $this->priceField()->setColumns(2);
        yield $this->stockField()->setColumns(2);
        yield $this->publishedField($pageName)->setHelp('')->setColumns(3);
    }

    private function sizeField(): ChoiceField
    {
        return ChoiceField::new('size', 'Taille')
            ->setChoices(MerchSize::cases())
            ->setFormTypeOption('choice_label', static fn (MerchSize $size): string => $size->label());
    }

    private function colorField(): TextField
    {
        return TextField::new('color', 'Couleur')->setFormTypeOption('attr.placeholder', 'noir, écru…');
    }
}
