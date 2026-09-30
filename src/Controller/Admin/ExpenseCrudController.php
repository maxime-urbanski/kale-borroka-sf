<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Expense;
use App\Enum\ExpenseCategory;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Vich\UploaderBundle\Form\Type\VichFileType;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * The label's expenses ("Frais du label"), taken off the available funds of the dashboard.
 *
 * @extends AbstractCrudController<Expense>
 */
#[IsGranted('ROLE_ADMIN')]
class ExpenseCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Expense::class;
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [StorageInterface::class]);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Frais')
            ->setEntityLabelInPlural('Frais du label')
            ->setDefaultSort(['paymentDueDate' => 'DESC', 'id' => 'DESC'])
            ->setSearchFields(['name', 'provider', 'description'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        $invoice = Action::new('invoice', 'Facture', 'fa fa-file-invoice')
            ->linkToCrudAction('invoice')
            ->displayIf(static fn (Expense $expense): bool => $expense->hasInvoice());

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $invoice)
            ->add(Crud::PAGE_DETAIL, $invoice)
            ->add(Crud::PAGE_EDIT, $invoice);
    }

    /**
     * Invoices live outside public/: this route, behind ROLE_ADMIN, is the only way to them.
     *
     * @param AdminContext<Expense> $context
     */
    #[AdminRoute(path: '/{entityId}/invoice', name: 'invoice', options: ['methods' => ['GET']])]
    public function invoice(AdminContext $context): BinaryFileResponse
    {
        $expense = $context->getEntity()->getInstance();
        \assert($expense instanceof Expense);
        $path = $expense->hasInvoice() ? $this->container->get(StorageInterface::class)->resolvePath($expense, 'invoiceFile') : null;

        if (null === $path || !is_file($path)) {
            throw $this->createNotFoundException('Aucune facture pour ces frais.');
        }

        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            // makeDisposition() throws on "/", "\" and "%", which a client file name may hold.
            str_replace(['/', '\\', '%'], '-', (string) ($expense->getInvoiceOriginalName() ?? $expense->getInvoiceName())),
            (string) $expense->getInvoiceName(),
        );
        $response->headers->set('Content-Type', $expense->getInvoiceEncodingFormat() ?? 'application/octet-stream');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    public function configureFilters(Filters $filters): Filters
    {
        $categories = [];
        foreach (ExpenseCategory::cases() as $category) {
            $categories[$category->label()] = $category->value;
        }

        return $filters
            ->add(ChoiceFilter::new('category', 'Catégorie')->setChoices($categories))
            ->add(DateTimeFilter::new('paymentDueDate', 'Date'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield DateField::new('paymentDueDate', 'Date')
            ->setHelp('Jour du paiement : c\'est à cette date que la somme sort des fonds. Une date future apparaît en « frais à venir » sur le tableau de bord.')
            ->setColumns(4);
        yield TextField::new('name', 'Libellé')
            ->setHelp('Ex. : pressage 300 LP « Nom de l\'album », 50 t-shirts sérigraphiés…')
            ->setColumns(8);
        yield ChoiceField::new('category', 'Catégorie')
            ->setChoices(ExpenseCategory::cases())
            ->setFormTypeOption('choice_label', static fn (ExpenseCategory $category): string => $category->label())
            ->formatValue(static fn (?ExpenseCategory $category): string => (string) $category?->label())
            ->setColumns(4);
        yield TextField::new('provider', 'Fournisseur')
            ->setColumns(4);
        yield MoneyField::new('totalPaymentDue', 'Montant TTC')
            ->setCurrency('EUR')
            ->setColumns(4);
        yield TextareaField::new('description', 'Notes')
            ->hideOnIndex()
            ->setColumns(12);
        yield Field::new('invoiceFile', 'Facture')
            ->setFormType(VichFileType::class)
            ->setFormTypeOptions([
                'required' => false,
                'allow_delete' => true,
                'download_uri' => false,
            ])
            ->setHelp('PDF ou image, 10 Mo max. Visible uniquement depuis l\'administration.')
            ->onlyOnForms()
            ->setColumns(12);
        yield TextField::new('invoiceOriginalName', 'Facture')
            ->formatValue(static fn (?string $name): string => $name ?? '—')
            ->hideOnForm();
    }
}
