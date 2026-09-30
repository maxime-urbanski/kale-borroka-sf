<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Book;
use App\Enum\BookType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractArticleCrudController<Book>
 */
class BookCrudController extends AbstractArticleCrudController
{
    public static function getEntityFqcn(): string
    {
        return Book::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setEntityLabelInSingular('Fanzine / livre')
            ->setEntityLabelInPlural('Fanzines & livres')
            ->setSearchFields(['name', 'author', 'isbn', 'sku']);
    }

    protected function configureSpecificFields(string $pageName): iterable
    {
        yield FormField::addFieldset('Publication', 'fa fa-book');
        yield $this->nameField()->setLabel('Titre')->setColumns(6);
        yield ChoiceField::new('bookType', 'Type')
            ->setChoices(BookType::cases())
            ->setFormTypeOption('choice_label', static fn (BookType $type): string => $type->label())
            ->renderExpanded()
            ->setColumns(3);
        yield AssociationField::new('category', 'Catégorie')
            ->setColumns(3)
            ->hideOnIndex();
        yield TextField::new('author', 'Auteur·ice')
            ->setColumns(6);
        yield AssociationField::new('publisher', 'Éditeur')
            ->setHelp('Parmi les labels.')
            ->setColumns(6)
            ->hideOnIndex();
        yield TextField::new('isbn', 'ISBN')
            ->setHelp('Si le livre en a un ; les fanzines rarement.')
            ->setColumns(4)
            ->hideOnIndex();
        yield IntegerField::new('numberOfPages', 'Pages')
            ->setColumns(2)
            ->hideOnIndex();
    }
}
