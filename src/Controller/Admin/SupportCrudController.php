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
        yield TextField::new('name', 'Segment d\'URL')
            ->setHelp('Doit rester égal au code : c\'est lui qui apparaît dans /catalog/…');
        yield ChoiceField::new('code', 'Rayon')
            ->setChoices(SupportType::cases());
        yield TextField::new('icon', 'Icône');
    }
}
