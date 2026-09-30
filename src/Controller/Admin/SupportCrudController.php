<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Support;
use App\Enum\SupportType;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<Support>
 */
class SupportCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Support::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield ChoiceField::new('code', 'Rayon')
            ->setChoices(SupportType::cases())
            ->setColumns(4);
        yield TextField::new('name', 'Segment d\'URL')
            ->setHelp('Doit rester égal au code : c\'est lui qui apparaît dans /catalog/…')
            ->setColumns(4);
        yield TextField::new('icon', 'Icône')
            ->setColumns(4);
    }
}
