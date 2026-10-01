<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Style;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * Read-only: the styles are the official list (Style::OFFICIAL), seeded by a migration.
 *
 * @extends AbstractCrudController<Style>
 */
class StyleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Style::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Styles')
            ->setEntityLabelInSingular('Style')
            ->setDefaultSort(['name' => 'ASC'])
            ->setHelp(Crud::PAGE_INDEX, 'Liste officielle, choisie depuis le formulaire album. Pour ajouter un style, demandez-le au développeur du site.');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW, Action::EDIT, Action::DELETE, Action::BATCH_DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name')
            ->setLabel('Style');
        yield AssociationField::new('albums', 'Albums')
            ->hideOnForm();
    }
}
