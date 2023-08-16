<?php

namespace App\Controller\Admin;

use App\Entity\Images;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class ImagesCrudController extends AbstractCrudController
{

    public static function getEntityFqcn(): string
    {
        return Images::class;
    }
}
