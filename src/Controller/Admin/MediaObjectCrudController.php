<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\MediaObject;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use Vich\UploaderBundle\Form\Type\VichImageType;

class MediaObjectCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return MediaObject::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield Field::new('file', 'Image')
            ->setLabel('Image actuelle')
            ->setFormType(VichImageType::class);
    }
}
