<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Image;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Vich\UploaderBundle\Form\Type\VichImageType;

/**
 * @extends AbstractCrudController<Image>
 */
class ImageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Image::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Image')
            ->setEntityLabelInPlural('Images')
            ->showEntityActionsInlined();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('album', 'Album'))
            ->add(EntityFilter::new('editions', 'Éditions'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield ImageField::new('imageName')
            ->setLabel('Aperçu')
            ->setBasePath('/upload/albums')
            ->setUploadDir('/upload/albums')
            ->setHelp('Aperçu du fichier enregistré.')
            ->onlyOnIndex();
        yield AssociationField::new('album', 'Albums')
            ->setHelp("Rattacher l'image à un album en fait la pochette générique, reprise par toutes ses éditions.")
            ->setColumns(6);
        yield AssociationField::new('editions', 'Éditions')
            ->setHelp("Rattacher l'image à une édition la réserve à ce pressage — la photo du vinyle rouge, par exemple. Elle prend alors le pas sur la pochette de l'album.")
            ->setColumns(6);

        yield Field::new('imageFile', 'Image')
            ->setFormType(VichImageType::class)
            ->setHelp('Fichier à envoyer. Remplace le précédent à chaque envoi.')
            ->onlyOnForms();
    }
}
