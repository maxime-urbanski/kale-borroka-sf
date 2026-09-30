<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\MediaObject;
use App\Repository\MediaObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Vich\UploaderBundle\Form\Type\VichImageType;

/**
 * The media library ("Médiathèque"): files used by other entities, e.g. social network
 * icons. Also the embedded form of SocialNetworkCrudController's icon field.
 *
 * @extends AbstractCrudController<MediaObject>
 */
class MediaObjectCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return MediaObject::class;
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [MediaObjectRepository::class]);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Média')
            ->setEntityLabelInPlural('Médiathèque')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['filename'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::BATCH_DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield ImageField::new('filename', 'Aperçu')
            ->setBasePath('/media')
            ->hideOnForm();
        yield TextField::new('filename', 'Adresse')
            ->formatValue(static fn (?string $filename): string => '/media/'.$filename)
            ->hideOnForm();
        yield Field::new('file', 'Fichier')
            ->setFormType(VichImageType::class)
            ->setFormTypeOptions([
                'required' => false,
                'allow_delete' => false,
                'download_uri' => false,
            ])
            ->setHelp('Image, 8 Mo max. Remplacer le fichier garde les liens vers ce média.')
            ->onlyOnForms();
        yield DateTimeField::new('updatedAt', 'Modifié le')
            ->hideOnForm();
    }

    /**
     * A social network pointing at a deleted file would fail on the foreign key.
     */
    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $usages = $this->container->get(MediaObjectRepository::class)->findUsages($entityInstance);

        if ([] !== $usages) {
            $this->addFlash('danger', \sprintf('Ce média est utilisé par : %s. Remplacez-le là-bas avant de le supprimer.', implode(', ', $usages)));

            return;
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }
}
