<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\SocialNetwork;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

/**
 * @extends AbstractCrudController<SocialNetwork>
 */
class SocialNetworkCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SocialNetwork::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addColumn(7);
        yield TextField::new('name', 'Nom');
        // http(s) only (SocialNetwork::$url): a mailto: or javascript: link is refused.
        yield UrlField::new('url', 'URL')
            ->setHelp('Adresse https://… de la page du réseau.');
        yield BooleanField::new('isPublish', 'Publié')->setColumns(6);
        yield BooleanField::new('inFooter', 'Dans le footer')->setColumns(6);

        yield FormField::addColumn(5);
        yield ImageField::new('file.filename')
            ->setLabel('Icône')
            ->setBasePath('/media')
            ->hideOnForm();
        yield AssociationField::new('file')
            ->setLabel('Icône')
            ->setHelp('Carrée, affichée en rond de 40 px dans le footer.')
            ->setCrudController(MediaObjectCrudController::class)
            ->renderAsEmbeddedForm()
            ->onlyOnForms();
    }
}
