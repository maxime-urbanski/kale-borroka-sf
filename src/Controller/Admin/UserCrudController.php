<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * Customers and back-office accounts alike (menu "Clients" and "Utilisateurs & rôles").
 * Accounts are created by registering or with app:create-admin, never from here, and
 * passwords only change through the reset-password flow. Deleting is disabled: orders
 * reference their buyer.
 *
 * @extends AbstractCrudController<User>
 */
class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Clients & utilisateurs')
            ->setDefaultSort(['created_at' => 'DESC'])
            ->setSearchFields(['email', 'firstname', 'lastname'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield EmailField::new('email', 'E-mail');
        yield TextField::new('firstname', 'Prénom');
        yield TextField::new('lastname', 'Nom');
        yield ChoiceField::new('roles', 'Rôles')
            ->setChoices(['Client' => 'ROLE_USER', 'Administrateur' => 'ROLE_ADMIN'])
            ->allowMultipleChoices()
            ->renderExpanded()
            ->renderAsBadges(['ROLE_ADMIN' => 'danger', 'ROLE_USER' => 'secondary'])
            ->setHelp('Un administrateur a accès à tout le back office.');
        yield DateTimeField::new('created_at', 'Inscrit le')
            ->hideOnForm();
        yield AssociationField::new('orders', 'Commandes')
            ->hideOnForm();
    }
}
