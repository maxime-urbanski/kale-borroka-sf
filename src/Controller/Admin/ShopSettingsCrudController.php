<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\ShopSettings;
use App\Repository\ShopSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

/**
 * A single row: the list page goes straight to its form, creating it on first visit.
 *
 * @extends AbstractCrudController<ShopSettings>
 */
class ShopSettingsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ShopSettings::class;
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [ShopSettingsRepository::class, EntityManagerInterface::class]);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Paramètres de la boutique')
            ->setEntityLabelInPlural('Paramètres de la boutique')
            ->setPageTitle(Crud::PAGE_EDIT, 'Paramètres de la boutique');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE, Action::SAVE_AND_CONTINUE);
    }

    public function index(AdminContext $context): Response
    {
        $repository = $this->container->get(ShopSettingsRepository::class);
        $settings = $repository->findOneBy([], ['id' => 'ASC']);

        if (null === $settings) {
            $settings = new ShopSettings();
            $entityManager = $this->container->get(EntityManagerInterface::class);
            $entityManager->persist($settings);
            $entityManager->flush();
        }

        return $this->redirect($this->container->get(AdminUrlGenerator::class)
            ->unsetAll()
            ->setController(self::class)
            ->setAction(Action::EDIT)
            ->setEntityId($settings->getId())
            ->generateUrl());
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addFieldset('Stock', 'fa fa-boxes-stacked');
        yield IntegerField::new('lowStockThreshold', 'Seuil de stock critique')
            ->setHelp('À ce stock ou en dessous, un article est signalé en orange dans les listes et le tableau de bord.')
            ->setColumns(4);
        yield FormField::addFieldset('Livraison', 'fa fa-truck');
        yield MoneyField::new('freeShippingThreshold', 'Livraison offerte dès')
            ->setCurrency('EUR')
            ->setHelp('Laisser vide : jamais offerte.')
            ->setColumns(4);
        yield FormField::addFieldset('Contact', 'fa fa-envelope');
        yield EmailField::new('contactEmail', 'E-mail de contact')
            ->setColumns(6);
    }
}
