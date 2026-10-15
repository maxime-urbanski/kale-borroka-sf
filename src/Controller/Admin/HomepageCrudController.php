<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Homepage;
use App\Enum\HomeShelf;
use App\Repository\HomepageRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * « Blocs de l'accueil »: a single row, the list page goes straight to its form, creating
 * it on first visit (like ShopSettingsCrudController).
 *
 * @extends AbstractCrudController<Homepage>
 */
#[IsGranted('ROLE_ADMIN')]
class HomepageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Homepage::class;
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [HomepageRepository::class, EntityManagerInterface::class]);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Blocs de l\'accueil')
            ->setEntityLabelInPlural('Blocs de l\'accueil')
            ->setPageTitle(Crud::PAGE_EDIT, 'Blocs de l\'accueil')
            ->setHelp(Crud::PAGE_EDIT, 'Les textes de la page d\'accueil, de haut en bas, avec le bandeau et le footer de toutes les pages. Le disque à la une se règle dans « Encart à la une », les colonnes du footer dans « Colonnes du footer ».');
    }

    public function configureActions(Actions $actions): Actions
    {
        $view = Action::new('viewOnSite', 'Voir l\'accueil', 'fa fa-arrow-up-right-from-square')
            ->linkToRoute('app_homepage')
            ->setHtmlAttributes(['target' => '_blank']);

        return $actions
            ->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE, Action::SAVE_AND_CONTINUE)
            ->add(Crud::PAGE_EDIT, $view);
    }

    public function index(AdminContext $context): Response
    {
        $homepage = $this->container->get(HomepageRepository::class)->findOneBy([], ['id' => 'ASC']);

        if (null === $homepage) {
            $homepage = new Homepage();
            $entityManager = $this->container->get(EntityManagerInterface::class);
            $entityManager->persist($homepage);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_homepage_edit', ['entityId' => $homepage->getId()]);
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab('Bandeau du haut', 'fa fa-grip-lines');
        yield BooleanField::new('stripShown', 'Afficher le bandeau')
            ->setHelp('Bande noire au-dessus du menu, sur toutes les pages, sur grand écran seulement.')
            ->renderAsSwitch(false)
            ->setColumns(12);
        yield TextField::new('stripLeft', 'Texte de gauche')
            ->setRequired(false)
            ->setColumns(6);
        yield TextField::new('stripRight', 'Texte de droite')
            ->setRequired(false)
            ->setColumns(6);

        yield FormField::addTab('Dernières prod', 'fa fa-record-vinyl');
        yield BooleanField::new('productionsShown', 'Afficher la section')
            ->setHelp('Les productions du label, du plus grand numéro KBR au plus petit, sous l\'encart à la une.')
            ->renderAsSwitch(false)
            ->setColumns(12);
        yield TextField::new('productionsTitle', 'Titre')
            ->setColumns(4);
        yield ChoiceField::new('productionsLimit', 'Nombre de disques')
            ->setChoices(array_combine(array_map(strval(...), Homepage::PRODUCTIONS_LIMITS), Homepage::PRODUCTIONS_LIMITS))
            ->setHelp('Par rangées de quatre.')
            ->setColumns(4);
        yield TextField::new('productionsLinkText', 'Lien vers « Nos productions »')
            ->setRequired(false)
            ->setHelp('Vide : pas de lien.')
            ->setColumns(4);

        yield FormField::addTab('Bloc « Le label »', 'fa fa-flag');
        yield BooleanField::new('manifestoShown', 'Afficher le bloc')
            ->setHelp('Bloc noir entre « Dernières prod » et « Derniers articles ».')
            ->renderAsSwitch(false)
            ->setColumns(12);
        yield TextField::new('manifestoKicker', 'Surtitre')
            ->setRequired(false)
            ->setHelp('Petite ligne au-dessus du slogan. Vide : pas de surtitre.')
            ->setColumns(6);
        yield BooleanField::new('manifestoRule', 'Filet rouge, vert, blanc devant le surtitre')
            ->renderAsSwitch(false)
            ->setColumns(6);
        yield TextareaField::new('manifestoHeadline', 'Slogan')
            ->setRequired(false)
            ->setNumOfRows(3)
            ->setHelp('Une ligne par phrase, en grandes capitales.')
            ->setColumns(6);
        yield TextField::new('manifestoHighlight', 'Dernière ligne, sur fond papier')
            ->setRequired(false)
            ->setHelp('Ex. : « Pas de fachos. ». Vide : pas de ligne surlignée.')
            ->setColumns(6);
        yield TextareaField::new('manifestoText', 'Texte')
            ->setRequired(false)
            ->setNumOfRows(4)
            ->setHelp('Texte brut ; les retours à la ligne sont gardés.')
            ->setColumns(12);
        yield FormField::addFieldset('Lien');
        yield TextField::new('manifestoLinkText', 'Texte du lien')
            ->setRequired(false)
            ->setHelp('Vide : pas de lien.')
            ->setColumns(4);
        yield AssociationField::new('manifestoPage', 'Vers une page du site')
            ->setRequired(false)
            ->setHelp('Le manifeste, « Qui sommes-nous »… Une page non publiée n\'a pas de lien.')
            ->setColumns(4);
        yield TextField::new('manifestoUrl', 'Ou vers une adresse')
            ->setRequired(false)
            ->setHelp('Si aucune page n\'est choisie : https://… ou un chemin du site comme /catalog.')
            ->setColumns(4);

        yield FormField::addTab('Derniers articles', 'fa fa-box-open');
        yield BooleanField::new('latestShown', 'Afficher la section')
            ->setHelp('Les derniers articles ajoutés au catalogue (date de création de la fiche), en bas de page.')
            ->renderAsSwitch(false)
            ->setColumns(12);
        yield TextField::new('latestTitle', 'Titre')
            ->setColumns(4);
        yield ChoiceField::new('latestLimit', 'Nombre d\'articles par rayon')
            ->setChoices(array_combine(array_map(strval(...), Homepage::LATEST_LIMITS), Homepage::LATEST_LIMITS))
            ->setHelp('Par rangées de six ; quatre sur téléphone.')
            ->setColumns(4);
        yield TextField::new('latestLinkText', 'Lien vers le catalogue')
            ->setRequired(false)
            ->setHelp('Vide : pas de lien.')
            ->setColumns(4);
        yield ChoiceField::new('latestShelves', 'Rayons proposés')
            ->setChoices(HomeShelf::cases())
            // The column holds strings: EasyAdmin cannot guess the enum.
            ->setFormTypeOption('class', HomeShelf::class)
            ->setFormTypeOption('choice_label', static fn (HomeShelf $shelf): string => $shelf->label())
            ->allowMultipleChoices()
            ->renderExpanded()
            ->setHelp('Un bouton par rayon au-dessus des articles, le premier affiché d\'office. Un rayon vide n\'a pas de bouton ; un seul rayon, pas de bouton du tout. Pas de merch : un t-shirt n\'a pas encore de page.')
            ->setColumns(12);

        yield FormField::addTab('Footer', 'fa fa-align-left');
        yield TextareaField::new('footerText', 'Texte sous le nom du label')
            ->setRequired(false)
            ->setNumOfRows(4)
            ->setHelp('Sur toutes les pages. Texte brut ; les retours à la ligne sont gardés. Vide : pas de texte. Les colonnes de liens se règlent dans « Colonnes du footer ».')
            ->setColumns(12);
        yield TextField::new('footerTagline', 'Devise après le copyright')
            ->setRequired(false)
            ->setHelp('« © année Kale Borroka Riot Records · devise », tout en bas. Vide : pas de devise.')
            ->setColumns(12);
    }
}
