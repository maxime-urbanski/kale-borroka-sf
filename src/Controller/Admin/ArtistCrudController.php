<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Artist;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Vich\UploaderBundle\Form\Type\VichImageType;

/**
 * @extends AbstractCrudController<Artist>
 */
class ArtistCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Artist::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Artistes')
            ->setEntityLabelInSingular('Artiste')
            ->setDefaultSort(['name' => 'ASC'])
            ->setSearchFields(['name', 'countryOfOrigin'])
            ->showEntityActionsInlined();
    }

    /**
     * Fields are listed explicitly: the auto-generated form would ask for the slug, which
     * Gedmo derives from the name on persist, and would choke on the JSON links column.
     */
    public function configureFields(string $pageName): iterable
    {
        yield ImageField::new('imageName', 'Photo')
            ->setBasePath('/upload/artists')
            ->setUploadDir('public/upload/artists')
            ->setHelp('Aperçu de la photo enregistrée.')
            ->onlyOnIndex();
        yield TextField::new('name', 'Nom')
            ->setHelp("Nom du groupe tel qu'il doit apparaître devant le titre de chaque album.")
            ->setColumns(6);
        yield TextField::new('countryOfOrigin', 'Pays')
            ->setHelp("Pays d'origine du groupe. Code ISO à 2 lettres (FR, US, DE, …).")
            ->setColumns(3);
        yield IntegerField::new('foundedYear', 'Année de formation')
            ->setHelp('Année de création du groupe. Purement informatif.')
            ->setColumns(3);
        yield TextEditorField::new('description', 'Description')
            ->setHelp('Biographie ou présentation du groupe.')
            ->hideOnIndex()
            ->setColumns(12);
        yield Field::new('imageFile', 'Photo')
            ->setFormType(VichImageType::class)
            ->setHelp('Photo du groupe. Remplace la précédente à chaque envoi.')
            ->onlyOnForms()
            ->setColumns(6);
        // links is a plateforme => URL map (schema.org sameAs). A keyed collection keeps
        // it editable without inventing an entity for two or three URLs per artist.
        yield ArrayField::new('links', 'Liens externes')
            ->setHelp('Profils du groupe ailleurs sur le web. Clé = plateforme (bandcamp, discogs…), valeur = URL complète.')
            ->setFormTypeOptions([
                'entry_type' => UrlType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'entry_options' => ['required' => false],
            ])
            ->hideOnIndex()
            ->setColumns(12);
    }
}
