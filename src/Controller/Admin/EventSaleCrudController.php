<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\EventSale;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Takings of the label's stands at concerts, fests and fairs ("Ventes en événement"),
 * added to the available funds of the dashboard.
 *
 * @extends AbstractCrudController<EventSale>
 */
#[IsGranted('ROLE_ADMIN')]
class EventSaleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return EventSale::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Vente en événement')
            ->setEntityLabelInPlural('Ventes en événement')
            ->setDefaultSort(['startTime' => 'DESC', 'id' => 'DESC'])
            ->setSearchFields(['name', 'location', 'description'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add(DateTimeFilter::new('startTime', 'Date'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield DateField::new('startTime', 'Date')
            ->setColumns(4);
        yield TextField::new('name', 'Événement')
            ->setHelp('Ex. : concert de soutien, fest, salon du disque…')
            ->setColumns(8);
        yield TextField::new('location', 'Lieu')
            ->setColumns(8);
        yield MoneyField::new('price', 'Recette totale')
            ->setCurrency('EUR')
            ->setHelp('Tout ce qui a été encaissé sur le stand (espèces, carte…).')
            ->setColumns(4);
        yield TextareaField::new('description', 'Notes')
            ->setHelp('Ce qui a été vendu, qui tenait le stand…')
            ->hideOnIndex()
            ->setColumns(12);
    }
}
