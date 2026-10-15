<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\FooterLink;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * Entry form of the links collection in FooterColumnCrudController (page names below).
 * No page of its own: a link is created, edited and deleted in its column's form.
 *
 * @extends AbstractCrudController<FooterLink>
 */
class FooterLinkCrudController extends AbstractCrudController
{
    public const string PAGE_IN_COLUMN_NEW = 'footer_link_new';
    public const string PAGE_IN_COLUMN_EDIT = 'footer_link_edit';

    public static function getEntityFqcn(): string
    {
        return FooterLink::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Lien du footer')
            ->setEntityLabelInPlural('Liens du footer');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::INDEX, Action::DETAIL, Action::NEW, Action::EDIT, Action::DELETE, Action::BATCH_DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Texte')
            ->setRequired(false)
            ->setHelp('Vide : le titre de la page ou le nom du rayon.')
            ->setColumns(3);
        yield AssociationField::new('page', 'Vers une page')
            ->setRequired(false)
            ->setColumns(3);
        yield AssociationField::new('support', 'Ou un rayon')
            ->setRequired(false)
            ->setColumns(2);
        yield TextField::new('url', 'Ou une adresse')
            ->setRequired(false)
            ->setHelp('https://… ou un chemin du site comme /catalog.')
            ->setColumns(3);
        yield IntegerField::new('position', 'Ordre')
            ->setRequired(false)
            ->setColumns(1);
    }
}
