<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Support;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class SupportCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Support::class;
    }
}
