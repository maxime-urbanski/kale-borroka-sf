<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\SocialNetwork;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
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
        yield TextField::new('name', 'Nom')
            ->setHelp('Nom du réseau, utilisé comme libellé du lien (Instagram, Bandcamp, Mail…).');

        yield UrlField::new('url', 'URL')
            ->setHelp('Adresse complète du profil. Pour une adresse mail, préfixez par mailto: — le lien est alors mis en forme automatiquement.')
            ->formatValue(function ($value) {
                if (str_contains($value, 'mailto')) {
                    $hrefLink = str_replace('http://', '', $value);
                    $textDisplay = str_replace('mailto:', '', $hrefLink);

                    return sprintf('<a href="%s">%s</a>', $hrefLink, $textDisplay);
                }

                return $value;
            })
        ;

        // basePath must match the Vich `media_object` uri_prefix (/media), and uploadDir
        // is resolved from the project root — the previous '/' + '/public/media' pair
        // produced a path that did not resolve.
        yield ImageField::new('file.filename')
            ->setLabel('Image')
            ->setBasePath('/media')
            ->setUploadDir('public/media')
            ->setHelp("Aperçu de l'icône enregistrée.")
            ->hideOnForm()
        ;

        yield AssociationField::new('file')
            ->setLabel('Image')
            ->setHelp('Icône affichée à côté du lien.')
            ->setCrudController(MediaObjectCrudController::class)
            ->renderAsEmbeddedForm()
            ->onlyOnForms()
        ;

        yield BooleanField::new('isPublish', 'Publier')
            ->setHelp('Décoché, le lien est conservé mais n\'apparaît nulle part sur le site.');
        yield BooleanField::new('inFooter', 'Ajouter au footer')
            ->setHelp('Affiche également le lien dans le pied de page, en plus du menu.');
    }
}
