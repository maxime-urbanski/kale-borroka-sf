<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ArticleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Article::class;
    }

    /**
     * @throws \Exception
     */
    public function createEntity(string $entityFqcn)
    {
        $entity = new $entityFqcn();
        $entity->setCreatedAt(
            new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'))
        );

        $entity->setUpdatedAt(
            new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'))
        );

        return $entity;
    }

    /**
     * @throws \Exception
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Article) {
            return;
        }

        $entityInstance->setUpdatedAt(
            new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'))
        );

        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }

    public function configureActions(Actions $actions): Actions
    {
        $viewArticle = Action::new('view', 'Voir la page de l\'article')
            ->displayAsLink()
            ->linkToRoute('app_catalog_show', fn (Article $article) => [
                'support' => $article->getSupport()?->getName(),
                'slug' => $article->getSlug(),
            ])
            ->setHtmlAttributes(['target' => '_blank'])
            ->setCssClass('btn btn-success');

        return $actions
            ->add(Crud::PAGE_EDIT, $viewArticle);
    }

    public function configureFields(string $pageName): iterable
    {
        yield DateTimeField::new('created_at', 'Ajouter le')
            ->setFormTypeOption('input', 'datetime_immutable')
            ->setTimezone('Europe/Paris')
            ->setColumns(3)
            ->setFormTypeOption('attr', ['readonly' => true])
            ->hideOnIndex();
        yield DateTimeField::new('updated_at', 'Modifier le')
            ->setFormTypeOption('input', 'datetime_immutable')
            ->setTimezone('Europe/Paris')
            ->setColumns(3)
            ->setFormTypeOption('attr', ['readonly' => true])
            ->hideOnIndex();
        yield SlugField::new('slug')
            ->setColumns(6)
            ->setTargetFieldName('name');
        yield AssociationField::new('album')
            ->setColumns(6);
        yield AssociationField::new('album')
            ->setColumns(6);
        yield TextField::new('name', 'Nom de l\'article')
            ->setColumns(6);
        yield AssociationField::new('support')
            ->setColumns(4);
        yield NumberField::new('quantity', 'Quantité disponible')
            ->setColumns(4);
        yield MoneyField::new('price', 'Prix du produit')
            ->setColumns(4)
            ->setCurrency('EUR');
    }
}
