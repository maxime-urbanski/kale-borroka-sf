<?php

namespace App\Controller\Admin;

use App\Entity\SocialNetwork;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

class SocialNetworkCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SocialNetwork::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Nom');

        yield UrlField::new('url', 'URL')
            ->formatValue(function ($value) {
                if (str_contains($value, 'mailto')) {
                    $hrefLink = str_replace('http://', '', $value);
                    $textDisplay = str_replace('mailto:', '', $hrefLink);

                    return sprintf('<a href="%s">%s</a>', $hrefLink, $textDisplay);
                }

                return $value;
            })
        ;

        yield ImageField::new('file.filename')
            ->setLabel('Image')
            ->setBasePath('/')
            ->setUploadDir('/public/media')
            ->hideOnForm()
        ;

        yield AssociationField::new('file')
            ->setLabel('Image')
            ->setCrudController(MediaObjectCrudController::class)
            ->renderAsEmbeddedForm()
            ->onlyOnForms()
        ;

        yield BooleanField::new('isPublish', 'Publier');
        yield BooleanField::new('inFooter', 'Ajouter au footer');
    }
}
