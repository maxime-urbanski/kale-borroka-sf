<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Book;
use App\Enum\BookType;
use App\Enum\ItemCondition;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<Book>
 */
class BookCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Book::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Fanzine / livre')
            ->setEntityLabelInPlural('Fanzines & livres')
            ->setSearchFields(['name', 'author', 'isbn', 'sku'])
            ->showEntityActionsInlined();
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Titre')
            ->setColumns(6);
        yield ChoiceField::new('bookType', 'Type')
            ->setChoices(BookType::cases())
            ->setFormTypeOption('choice_label', static fn (BookType $type): string => $type->label())
            ->setColumns(3);
        yield TextField::new('author', 'Auteur·ice')
            ->setColumns(3);
        yield AssociationField::new('publisher', 'Éditeur')
            ->setColumns(3)
            ->hideOnIndex();
        yield AssociationField::new('category', 'Catégorie')
            ->setColumns(3)
            ->hideOnIndex();
        yield TextField::new('isbn', 'ISBN')
            ->setColumns(3)
            ->hideOnIndex();
        yield IntegerField::new('numberOfPages', 'Pages')
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
        yield IntegerField::new('stock', 'Stock')
            ->setColumns(3);
        yield MoneyField::new('price', 'Prix')
            ->setCurrency('EUR')
            ->setColumns(3);
        yield BooleanField::new('published', 'Publié');
    }
}
