<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Admin\Query\RevenuePeriod;
use App\Entity\FeaturedRelease;
use App\Enum\FeatureKind;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The record at the top of the homepage ("Encart de l'accueil"): only one shows at a time,
 * see FeaturedReleaseRepository::findCurrent().
 *
 * @extends AbstractCrudController<FeaturedRelease>
 */
#[IsGranted('ROLE_ADMIN')]
class FeaturedReleaseCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FeaturedRelease::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Encart de l\'accueil')
            ->setEntityLabelInPlural('Encarts de l\'accueil')
            ->setPageTitle(Crud::PAGE_INDEX, 'Encarts de l\'accueil')
            ->setHelp(Crud::PAGE_INDEX, 'Un seul encart s\'affiche en haut de l\'accueil : le plus récemment programmé parmi ceux en ligne, dont le disque est publié. Sans date de début, un encart sert de remplaçant.')
            ->setDefaultSort(['datePublished' => 'DESC', 'id' => 'DESC'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addColumn(8);
        yield ChoiceField::new('kind', 'Type')
            ->setChoices(FeatureKind::cases())
            ->setFormTypeOption('choice_label', static fn (FeatureKind $kind): string => $kind->label())
            ->formatValue(static fn (mixed $value, FeaturedRelease $feature): string => $feature->getKind()->label())
            ->setHelp('Prévente : bouton « Précommander », dans la limite du stock du pressage (le nombre d\'exemplaires ouverts à la précommande). Coup de cœur, À découvrir : « Ajouter au panier ».');
        yield AssociationField::new('release', 'Disque')
            ->setCrudController(ReleaseCrudController::class)
            ->autocomplete()
            ->setHelp('Le pressage vendu par le bouton. Il doit être publié pour que l\'encart s\'affiche.');
        yield TextField::new('headline', 'Accroche')
            ->setRequired(false)
            ->setHelp('Petite ligne au-dessus du titre. Vide : « Prochaine sortie du label », « Une pépite de la distro »… selon le type.')
            ->hideOnIndex();
        yield TextareaField::new('description', 'Texte')
            ->setRequired(false)
            ->setHelp('Texte brut, deux ou trois phrases. Vide : la note de l\'album.')
            ->hideOnIndex();
        yield DateField::new('releaseDate', 'Date de sortie')
            ->setRequired(false)
            ->setHelp('Prévente : affichée sur le tampon (« Prévente · sortie le 14.11 »).');
        yield UrlField::new('listenUrl', 'Lien d\'écoute')
            ->setRequired(false)
            ->setHelp('Bandcamp, YouTube… Bouton « Écouter » à côté du bouton d\'achat.')
            ->hideOnIndex();

        yield FormField::addColumn(4);
        yield BooleanField::new('published', 'En ligne');
        yield $this->shopTime(DateTimeField::new('datePublished', 'Afficher à partir du'))
            ->setRequired(false)
            ->setHelp('Vide : dès sa mise en ligne.');
        yield $this->shopTime(DateTimeField::new('expires', 'Jusqu\'au'))
            ->setRequired(false)
            ->setHelp('Vide : jusqu\'à ce qu\'il soit retiré. Pour une prévente, la date de sortie.');
    }

    /**
     * Typed and shown in shop time, stored in UTC like the clock: setTimezone() alone only
     * changes the display, the form would read the typed time as UTC.
     */
    private function shopTime(DateTimeField $field): DateTimeField
    {
        return $field
            ->setTimezone(RevenuePeriod::TIMEZONE)
            ->setFormTypeOption('model_timezone', 'UTC')
            ->setFormTypeOption('view_timezone', RevenuePeriod::TIMEZONE);
    }
}
