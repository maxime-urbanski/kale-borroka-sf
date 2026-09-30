<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Image;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use Vich\UploaderBundle\Form\Type\VichImageType;

/**
 * Album pictures. Also the entry form of the "Visuels" tabs (album, article and merch
 * forms), under the page names below, where the owner is implied.
 *
 * @extends AbstractCrudController<Image>
 */
class ImageCrudController extends AbstractCrudController
{
    /** Page names of the entry form, when embedded in an album, article or merch form. */
    public const string PAGE_EMBEDDED_NEW = 'embedded_image_new';
    public const string PAGE_EMBEDDED_EDIT = 'embedded_image_edit';

    public static function getEntityFqcn(): string
    {
        return Image::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Visuel')
            ->setEntityLabelInPlural('Visuels')
            ->setDefaultSort(['updatedAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        $embedded = \in_array($pageName, [self::PAGE_EMBEDDED_NEW, self::PAGE_EMBEDDED_EDIT], true);

        yield ImageField::new('imageName', 'Aperçu')
            ->setBasePath('/upload/albums')
            ->onlyOnIndex();
        yield Field::new('imageFile', $embedded ? false : 'Fichier')
            ->setFormType(VichImageType::class)
            ->setFormTypeOptions([
                'required' => false,
                // Removing a picture is done by removing the whole entry.
                'allow_delete' => false,
                'download_uri' => false,
            ])
            ->setHelp('JPEG, PNG ou WebP, 8 Mo max.')
            ->setColumns($embedded ? 9 : 12)
            ->onlyOnForms();
        yield IntegerField::new('position', 'Ordre')
            ->setHelp('Le plus petit est la pochette.')
            ->setRequired(false)
            ->setColumns(3);

        if (!$embedded) {
            yield AssociationField::new('album', 'Albums');
        }
    }
}
