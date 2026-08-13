<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Edition;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

/**
 * The pressing: what makes a red vinyl different from a black one.
 *
 * @extends AbstractCrudController<Edition>
 */
class EditionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Edition::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Édition')
            ->setEntityLabelInPlural('Éditions')
            ->setDefaultSort(['name' => 'ASC'])
            ->setSearchFields(['name', 'color', 'catalogNumber', 'album.name'])
            ->showEntityActionsInlined();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('support', 'Support'))
            ->add(EntityFilter::new('album', 'Album'))
            ->add(TextFilter::new('color', 'Couleur'))
            ->add(TextFilter::new('editionLabel', 'Mention d\'édition'))
            ->add(NumericFilter::new('pressingRun', 'Tirage'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab('Pressage')
            ->setHelp("Une édition est un pressage précis d'un album. Deux vinyles de couleurs différentes du même disque font deux éditions ; c'est entre elles que bascule le sélecteur sur la page publique.")
            ->onlyOnForms();
        yield AssociationField::new('album', 'Album')
            ->autocomplete()
            ->setHelp('Disque dont cette édition est un pressage.')
            ->setColumns(6);
        yield AssociationField::new('support', 'Support')
            ->setHelp("Format physique : LP, EP, CD, K7, fanzine. Il détermine sous quelle rubrique du catalogue l'édition apparaît.")
            ->setColumns(3);
        yield TextField::new('name', 'Nom de l\'édition')
            ->setHelp('Ce que le sélecteur affiche : « LP », « LP vinyle rouge », « CD digipack ». Court et distinctif.')
            ->setColumns(6);
        yield TextField::new('color', 'Couleur')
            ->setHelp('Couleur du vinyle : « rouge », « splatter vert/noir ». À laisser vide pour un CD ou un pressage noir standard.')
            ->setColumns(3);
        yield TextField::new('editionLabel', 'Mention d\'édition')
            ->setHelp('« Collector », « Édition limitée », « Réédition 2024 ». Mise en avant sous forme de badge sur la page.')
            ->hideOnIndex()
            ->setColumns(3);
        yield TextField::new('catalogNumber', 'N° de catalogue')
            ->setHelp('Référence du label pour ce pressage. Sert à identifier une édition sans ambiguïté.')
            ->hideOnIndex()
            ->setColumns(3);
        yield IntegerField::new('pressingRun', 'Tirage')
            ->setHelp('Nombre d\'exemplaires pressés, pour les tirages limités. Affiché tel quel au client.')
            ->hideOnIndex()
            ->setColumns(3);
        yield DateField::new('releaseDate', 'Date de sortie')
            ->setHelp("Date de sortie de ce pressage, quand elle diffère de celle de l'album — cas d'une réédition.")
            ->hideOnIndex()
            ->setColumns(3);
        yield TextareaField::new('description', 'Description')
            ->setHelp('Précisions propres à ce pressage : encart, poster inclus, gravure… Affiché sous le sélecteur.')
            ->hideOnIndex()
            ->setColumns(12);

        yield FormField::addTab('Visuels')->onlyOnForms();
        yield AssociationField::new('images', 'Visuels propres à l\'édition')
            ->setHelp("Photos de ce pressage précis, par exemple le vinyle rouge. Laisser vide pour réutiliser la pochette de l'album.")
            ->hideOnIndex()
            ->setColumns(12);

        yield FormField::addTab('Offres')->onlyOnForms();
        yield CollectionField::new('articles', 'Offres')
            ->useEntryCrudForm(ArticleCrudController::class)
            ->setHelp("Ce qui est réellement vendu : prix et stock. Plusieurs offres pour un même pressage permettent de proposer un exemplaire neuf et un d'occasion à des prix différents. Sans offre, l'édition n'est pas achetable.")
            ->hideOnIndex()
            ->setColumns(12);
    }
}
