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
            ->setBasePath('/upload/albums')
            ->setUploadDir('/upload/albums')
            ->onlyOnIndex();
        yield AssociationField::new('album', 'Albums')
            ->setHelp('Pochette générique, valable pour toutes les éditions.')
            ->setColumns(6);
        yield AssociationField::new('editions', 'Éditions')
            ->setHelp('Visuel propre à un pressage — la photo du vinyle rouge, par exemple.')
            ->setColumns(6);

        yield Field::new('imageFile', 'Image')
            ->setFormType(VichImageType::class)
            ->onlyOnForms();
    }
}
