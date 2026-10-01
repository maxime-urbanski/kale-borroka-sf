<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\Trait\RemovesOrphanImagesTrait;
use App\Entity\Album;
use App\Entity\Artist;
use App\Entity\Label;
use App\Enum\AlbumReleaseType;
use App\Repository\ImageRepository;
use App\Service\ShopSettingsProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

/**
 * @extends AbstractCrudController<Album>
 */
class AlbumCrudController extends AbstractCrudController
{
    use RemovesOrphanImagesTrait;

    public function __construct(
        private readonly ShopSettingsProviderInterface $shopSettingsProvider,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Album::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Albums')
            ->setEntityLabelInSingular('Album')
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        $addRelease = Action::new('addRelease', 'Pressage', 'fa fa-plus')
            ->linkToUrl(fn (Album $album): string => $this->container->get(AdminUrlGenerator::class)
                ->unsetAll()
                ->setController(ReleaseCrudController::class)
                ->setAction(Action::NEW)
                ->set('album', $album->getId())
                ->generateUrl());

        return $actions
            ->add(Crud::PAGE_INDEX, $addRelease)
            ->add(Crud::PAGE_EDIT, $addRelease)
            ->add(Crud::PAGE_DETAIL, $addRelease)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    /**
     * Pictures removed in the "Visuels" tab are deleted, file included, once nothing else
     * shows them (another album, an article, a merch design).
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $removed = $this->removedImages($entityInstance->getImages());
        parent::updateEntity($entityManager, $entityInstance);
        $this->deleteOrphanImages($entityManager, $removed);
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [ImageRepository::class]);
    }

    public function configureFields(string $pageName): iterable
    {
        // List only.
        yield ImageField::new('coverImageName')
            ->setLabel('Visuel')
            ->setBasePath('/upload/albums')
            ->setSortable(false)
            ->onlyOnIndex();

        yield FormField::addTab('Infos', 'fa fa-circle-info');
        yield FormField::addFieldset('Identité', 'fa fa-record-vinyl');
        yield AssociationField::new('artist')
            ->autocomplete()
            ->setFormTypeOption('create_missing', static fn (string $name): Artist => (new Artist())->setName($name))
            ->setLabel('Artiste')
            ->setHelp('Tapez le nom : un groupe absent de la liste est créé à l\'enregistrement.')
            ->setColumns(6);
        yield TextField::new('name')
            ->setLabel("Nom de l'album")
            ->setColumns(6);
        yield ChoiceField::new('releaseType')
            ->setLabel('Type')
            ->setChoices(AlbumReleaseType::cases())
            ->setFormTypeOption('choice_label', static fn (AlbumReleaseType $type): string => $type->label())
            ->setHelp('Un 12" d\'un EP est rangé dans le rayon EP.')
            ->setColumns(4);
        yield DateField::new('date_release')
            ->setLabel('Date de sortie')
            ->setColumns(4);
        yield BooleanField::new('kbrProduction')
            ->setLabel('Production K.B.R')
            ->setHelp('Numérotée KBR#… automatiquement.')
            ->setColumns(4);

        yield FormField::addFieldset('Classement', 'fa fa-tags')
            ->setHelp('Utilisés par les filtres du catalogue et les suggestions « dans le même style ».');
        yield AssociationField::new('styles')
            ->setQueryBuilder(static fn (QueryBuilder $query): QueryBuilder => $query->orderBy('entity.name', 'ASC'))
            ->setHelp('Liste officielle de styles : pour en ajouter un, demandez-le au développeur du site.')
            ->setColumns(6)
            ->hideOnIndex();
        yield AssociationField::new('labels')
            ->setLabel('Produit par')
            ->autocomplete()
            ->setFormTypeOption('create_missing', static fn (string $name): Label => (new Label())->setName($name))
            ->setColumns(6);

        yield FormField::addFieldset('Présentation', 'fa fa-align-left')->collapsible();
        yield TextareaField::new('note')
            ->setLabel(false)
            ->setHelp('Chronique, contexte, crédits… affichés sur la fiche.')
            ->setColumns(12)
            ->hideOnIndex();

        yield FormField::addTab('Tracklist', 'fa fa-list-ol')
            ->setBadge(static fn (?Album $album): ?int => $album?->getTracklists()->count() ?: null);
        yield CollectionField::new('tracklists')
            ->setLabel(false)
            ->useEntryCrudForm(SongCrudController::class)
            // The detail page would join every track on one line.
            ->setTemplatePath('admin/field/tracklist.html.twig')
            ->setColumns(12)
            ->hideOnIndex();

        yield FormField::addTab('Pressages', 'fa fa-compact-disc')
            ->setBadge(static fn (?Album $album): ?int => $album?->getReleases()->count() ?: null);
        yield CollectionField::new('releases')
            ->setLabel(false)
            ->setHelp('Chaque pressage (LP noir, LP rouge, CD…) est un article en vente avec son propre stock.')
            ->useEntryCrudForm(ReleaseCrudController::class, ReleaseCrudController::PAGE_IN_ALBUM_NEW, ReleaseCrudController::PAGE_IN_ALBUM_EDIT)
            ->setFormTypeOption('by_reference', false)
            ->setColumns(12)
            ->onlyOnForms();
        yield AssociationField::new('releases')
            ->setLabel('Pressages')
            ->onlyOnIndex();
        yield CollectionField::new('releases')
            ->setLabel(false)
            ->setTemplatePath('admin/field/pressings.html.twig')
            // Same badges as the stock column of « Exemplaires & pressages ».
            ->setCustomOption('lowStockThreshold', Crud::PAGE_DETAIL === $pageName ? $this->shopSettingsProvider->get()->getLowStockThreshold() : null)
            ->onlyOnDetail();

        yield FormField::addTab('Visuels', 'fa fa-image')
            ->setBadge(static fn (?Album $album): ?int => $album?->getImages()->count() ?: null);
        yield CollectionField::new('images')
            ->setLabel(false)
            ->setHelp('Le visuel au plus petit numéro d\'ordre sert de pochette. Retirer un visuel le supprime s\'il n\'est utilisé nulle part ailleurs.')
            ->useEntryCrudForm(ImageCrudController::class, ImageCrudController::PAGE_EMBEDDED_NEW, ImageCrudController::PAGE_EMBEDDED_EDIT)
            ->setFormTypeOption('by_reference', false)
            ->setColumns(12)
            ->onlyOnForms();
    }
}
