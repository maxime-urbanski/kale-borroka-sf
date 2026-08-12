<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Support;
use App\Enum\SupportType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
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

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Support')
            ->setEntityLabelInPlural('Supports')
            ->setDefaultSort(['name' => 'ASC'])
            ->showEntityActionsInlined();
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Nom')
            ->setHelp('Segment utilisé dans les URLs du catalogue (/catalog/lp, /catalog/cd, …).')
            ->setColumns(4);
        yield ChoiceField::new('code', 'Format')
            ->setChoices(array_combine(
                array_map(static fn (SupportType $type): string => $type->label(), SupportType::cases()),
                SupportType::cases(),
            ))
            ->renderExpanded(false)
            ->setColumns(4);
        yield TextField::new('icon', 'Icône')
            ->setColumns(4);
    }
}
