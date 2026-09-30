<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Release;
use App\Enum\ReleaseFormat;
use App\Repository\AlbumRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

/**
 * Pressings ("Exemplaires & pressages"). Also the entry form of the releases collection
 * in AlbumCrudController, under the page names below, where the album is implied.
 *
 * @extends AbstractArticleCrudController<Release>
 */
class ReleaseCrudController extends AbstractArticleCrudController
{
    public const string PAGE_IN_ALBUM_NEW = 'album_release_new';
    public const string PAGE_IN_ALBUM_EDIT = 'album_release_edit';

    public static function getEntityFqcn(): string
    {
        return Release::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setEntityLabelInSingular('Pressage')
            ->setEntityLabelInPlural('Exemplaires & pressages')
            ->setSearchFields(['name', 'sku', 'gtin', 'catalogNumber', 'color', 'album.name', 'album.artist.name']);
    }

    /**
     * "+ Pressage" on an album links here with ?album=<id>.
     */
    public function createEntity(string $entityFqcn): Release
    {
        $release = new Release();
        $albumId = $this->getContext()?->getRequest()->query->getInt('album');

        if ($albumId > 0) {
            $release->setAlbum($this->container->get(AlbumRepository::class)->find($albumId));
        }

        return $release;
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [AlbumRepository::class]);
    }

    public function configureActions(Actions $actions): Actions
    {
        $viewOnSite = Action::new('viewOnSite', 'Voir sur le site', 'fa fa-arrow-up-right-from-square')
            ->linkToRoute('app_catalog_show', static fn (Release $release): array => [
                'support' => $release->getSupportType()?->value,
                'slug' => $release->getSlug(),
            ])
            ->displayIf(static fn (Release $release): bool => $release->isPublished())
            ->setHtmlAttributes(['target' => '_blank']);

        return parent::configureActions($actions)
            ->add(Crud::PAGE_EDIT, $viewOnSite)
            ->add(Crud::PAGE_DETAIL, $viewOnSite);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return parent::configureFilters($filters)
            ->add(EntityFilter::new('album', 'Album'))
            ->add(ChoiceFilter::new('format', 'Format')->setChoices(array_combine(
                array_map(static fn (ReleaseFormat $format): string => $format->label(), ReleaseFormat::cases()),
                ReleaseFormat::cases(),
            )));
    }

    protected function isEmbedded(string $pageName): bool
    {
        return \in_array($pageName, [self::PAGE_IN_ALBUM_NEW, self::PAGE_IN_ALBUM_EDIT], true);
    }

    protected function fallbackPicturesLabel(): string
    {
        return 'Sans photo propre, les visuels de l\'album sont affichés.';
    }

    protected function configureSpecificFields(string $pageName): iterable
    {
        yield FormField::addFieldset('Identification', 'fa fa-record-vinyl');
        yield AssociationField::new('album', 'Album')
            ->autocomplete()
            ->setHelp(\sprintf(
                'Album absent ? <a href="%s" target="_blank">Créez-le</a>, puis revenez ici.',
                $this->adminUrl()->setController(AlbumCrudController::class)->setAction(Action::NEW)->generateUrl(),
            ))
            ->setColumns(6);
        yield $this->nameField()
            ->setHelp('Tel qu\'affiché en boutique : « Brixton Cats - Quartier Maudit (LP rouge) ».')
            ->setColumns(6);

        yield FormField::addFieldset('Pressage', 'fa fa-compact-disc');
        yield $this->formatField()->setColumns(3);
        yield $this->colorField()->setColumns(3);
        yield $this->editionField()->setColumns(3)->hideOnIndex();
        yield $this->limitedToField()->setColumns(3)->hideOnIndex();
        yield TextField::new('catalogNumber', 'N° de catalogue')
            ->setHelp('KBR#012, ou la référence du label.')
            ->setColumns(3)
            ->hideOnIndex();
        yield IntegerField::new('pressingYear', 'Année de pressage')
            ->setColumns(3)
            ->hideOnIndex();
        yield AssociationField::new('label', 'Label (distro)')
            ->setHelp('Si le disque n\'est pas une production KBR.')
            ->setColumns(6)
            ->hideOnIndex();
    }

    /**
     * Two rows per pressing in the album form: what tells pressings apart, then how it sells.
     * SKU, barcode, label and pictures are completed from the pressing's own page.
     */
    protected function configureEmbeddedFields(string $pageName): iterable
    {
        yield $this->nameField()->setColumns(4);
        yield $this->formatField()->setColumns(2);
        yield $this->colorField()->setColumns(2);
        yield $this->priceField()->setColumns(2);
        yield $this->stockField()->setColumns(2);
        yield $this->editionField()->setColumns(3);
        yield $this->limitedToField()->setColumns(2);
        yield $this->conditionField()->setColumns(3);
        yield $this->publishedField($pageName)
            ->setHelp('SKU, code-barres, photos : depuis « Exemplaires & pressages ».')
            ->setColumns(4);
    }

    private function formatField(): ChoiceField
    {
        return ChoiceField::new('format', 'Format')
            ->setChoices(ReleaseFormat::cases())
            ->setFormTypeOption('choice_label', static fn (ReleaseFormat $format): string => $format->label());
    }

    private function colorField(): TextField
    {
        return TextField::new('color', 'Couleur')->setFormTypeOption('attr.placeholder', 'noir, rouge translucide…');
    }

    private function editionField(): TextField
    {
        return TextField::new('editionLabel', 'Édition')->setFormTypeOption('attr.placeholder', 'Réédition 2024, limitée…');
    }

    private function limitedToField(): IntegerField
    {
        return IntegerField::new('limitedTo', 'Tirage limité à')->setFormTypeOption('attr.placeholder', 'ex. 300');
    }
}
