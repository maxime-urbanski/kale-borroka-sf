<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Artist;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

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
            ->showEntityActionsInlined();
    }

    /**
     * Short form in two fieldsets; the discography and merch show on the detail page.
     */
    public function configureFields(string $pageName): iterable
    {
        yield FormField::addFieldset('Profil', 'fa fa-microphone-lines');
        yield TextField::new('name', 'Nom')->setColumns(8);
        yield CountryField::new('country', 'Pays')->setColumns(4);
        yield TextareaField::new('description', 'Présentation')
            ->setHelp('Quelques lignes sur le groupe, affichées sur sa page.')
            ->setColumns(12)
            ->hideOnIndex();

        yield FormField::addFieldset('Liens officiels', 'fa fa-link')
            ->setHelp('Bandcamp, Instagram, site… Ils aident les moteurs de recherche à reconnaître le groupe.');
        yield ArrayField::new('links', false)
            ->setFormTypeOption('entry_type', UrlType::class)
            ->setFormTypeOption('entry_options', ['default_protocol' => 'https', 'attr' => ['placeholder' => 'https://…']])
            ->setColumns(8)
            ->hideOnIndex();

        yield FormField::addFieldset('Discographie & merch', 'fa fa-record-vinyl')
            ->onlyOnDetail();
        yield AssociationField::new('albums', 'Albums')
            ->hideOnForm();
        yield AssociationField::new('merches', 'Merch')
            ->onlyOnDetail();
    }
}
