<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Page;
use App\Enum\FooterPlacement;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<Page>
 */
class PageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Page::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Page')
            ->setEntityLabelInPlural('Pages')
            ->setDefaultSort(['footerPlacement' => 'ASC', 'footerPosition' => 'ASC'])
            ->showEntityActionsInlined();
    }

    /**
     * Works for unpublished pages too: admins get a preview.
     */
    public function configureActions(Actions $actions): Actions
    {
        $view = Action::new('viewOnSite', 'Voir la page', 'fa fa-arrow-up-right-from-square')
            ->linkToRoute('app_page_show', static fn (Page $page): array => ['slug' => $page->getSlug()])
            ->setHtmlAttributes(['target' => '_blank']);

        return $actions
            ->add(Crud::PAGE_INDEX, $view)
            ->add(Crud::PAGE_EDIT, $view);
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addColumn(8);
        yield TextField::new('title', 'Titre');
        yield SlugField::new('slug', 'Adresse')
            ->setTargetFieldName('title')
            ->setRequired(false)
            ->setHelp('La page sera en ligne à /page/<adresse>. Générée depuis le titre si vide ; la modifier casse les liens existants.')
            ->setUnlockConfirmationMessage('Changer l\'adresse casse les liens déjà partagés vers cette page. Continuer ?');
        yield TextEditorField::new('content', 'Contenu')
            ->hideOnIndex();

        yield FormField::addColumn(4);
        yield BooleanField::new('published', 'Publiée')
            ->setHelp('Non publiée : introuvable pour les visiteurs, visible en aperçu par les administrateurs.');
        yield ChoiceField::new('footerPlacement', 'Dans le footer')
            ->setChoices(FooterPlacement::cases())
            ->setFormTypeOption('choice_label', static fn (FooterPlacement $placement): string => $placement->label());
        yield IntegerField::new('footerPosition', 'Ordre dans le footer')
            ->setHelp('Les plus petits nombres d\'abord.')
            ->setRequired(false);
        yield DateTimeField::new('updatedAt', 'Modifiée le')
            ->hideOnForm();
    }
}
