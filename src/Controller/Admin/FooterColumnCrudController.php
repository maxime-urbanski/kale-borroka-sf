<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\FooterColumn;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * « Colonnes du footer »: as many columns of links as wanted, between the label's text and
 * « Suivre ». Each link is edited inside its column (FooterLinkCrudController).
 *
 * @extends AbstractCrudController<FooterColumn>
 */
class FooterColumnCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FooterColumn::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Colonne du footer')
            ->setEntityLabelInPlural('Colonnes du footer')
            ->setDefaultSort(['position' => 'ASC', 'id' => 'ASC'])
            ->setHelp(Crud::PAGE_INDEX, 'Les colonnes de liens du footer, de gauche à droite, entre le texte du label et « Suivre ». Le texte se règle dans « Blocs de l\'accueil », les réseaux dans « Réseaux sociaux », les liens du bas (CGV…) dans chaque page.')
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        $view = Action::new('viewOnSite', 'Voir le site', 'fa fa-arrow-up-right-from-square')
            ->linkToRoute('app_homepage')
            ->setHtmlAttributes(['target' => '_blank'])
            ->createAsGlobalAction();

        return $actions
            ->add(Crud::PAGE_INDEX, $view);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Titre')
            ->setHelp('Ex. : « À propos », « Shop ».')
            ->setColumns(8);
        yield IntegerField::new('position', 'Ordre')
            ->setHelp('De gauche à droite, les plus petits nombres d\'abord.')
            ->setRequired(false)
            ->setColumns(4);
        yield BooleanField::new('supportsListed', 'Tous les rayons du catalogue')
            ->setHelp('En tête de colonne, un lien par rayon (LP, CD…), tenu à jour tout seul quand un rayon est ajouté.')
            ->renderAsSwitch(false)
            ->setColumns(12);
        yield CollectionField::new('links', 'Liens')
            ->setHelp('De haut en bas, par ordre croissant. Un lien vers une page non publiée n\'est pas affiché ; une colonne sans lien affiché non plus.')
            ->useEntryCrudForm(FooterLinkCrudController::class, FooterLinkCrudController::PAGE_IN_COLUMN_NEW, FooterLinkCrudController::PAGE_IN_COLUMN_EDIT)
            ->setFormTypeOption('by_reference', false)
            ->setColumns(12);
    }
}
