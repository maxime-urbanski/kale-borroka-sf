<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ExpenseCategory;
use App\Repository\ExpenseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Money spent by the name (pressing, t-shirts, postage…), taken off the available funds.
 * Modelled on https://schema.org/Invoice: name, description, category, provider,
 * totalPaymentDue and paymentDueDate (the day the money leaves the name's account).
 *
 * The invoice is stored outside public/ (`expense_invoice` mapping): download it through
 * ExpenseCrudController::invoice(), which only admins can reach.
 */
#[ORM\Entity(repositoryClass: ExpenseRepository::class)]
#[Vich\Uploadable]
class Expense
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 20, enumType: ExpenseCategory::class)]
    #[Assert\NotNull]
    private ?ExpenseCategory $category = null;

    /** Cents, VAT included. */
    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Positive]
    private ?int $totalPaymentDue = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $paymentDueDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $provider = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[Vich\UploadableField(mapping: 'expense_invoice', fileNameProperty: 'invoiceName', originalName: 'invoiceOriginalName', mimeType: 'invoiceEncodingFormat')]
    #[Assert\File(maxSize: '10M', mimeTypes: ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], mimeTypesMessage: 'PDF ou image uniquement (JPEG, PNG, WebP).')]
    private ?File $invoiceFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $invoiceName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $invoiceOriginalName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $invoiceEncodingFormat = null;

    /** Vich only notices a replaced file if a mapped column changes. */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCategory(): ?ExpenseCategory
    {
        return $this->category;
    }

    public function setCategory(?ExpenseCategory $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getTotalPaymentDue(): ?int
    {
        return $this->totalPaymentDue;
    }

    public function setTotalPaymentDue(?int $totalPaymentDue): static
    {
        $this->totalPaymentDue = $totalPaymentDue;

        return $this;
    }

    public function getPaymentDueDate(): ?\DateTimeImmutable
    {
        return $this->paymentDueDate;
    }

    public function setPaymentDueDate(?\DateTimeImmutable $paymentDueDate): static
    {
        $this->paymentDueDate = $paymentDueDate;

        return $this;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(?string $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getInvoiceFile(): ?File
    {
        return $this->invoiceFile;
    }

    public function setInvoiceFile(?File $invoiceFile): static
    {
        $this->invoiceFile = $invoiceFile;

        if (null !== $invoiceFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getInvoiceName(): ?string
    {
        return $this->invoiceName;
    }

    public function setInvoiceName(?string $invoiceName): static
    {
        $this->invoiceName = $invoiceName;

        return $this;
    }

    public function getInvoiceOriginalName(): ?string
    {
        return $this->invoiceOriginalName;
    }

    public function setInvoiceOriginalName(?string $invoiceOriginalName): static
    {
        $this->invoiceOriginalName = $invoiceOriginalName;

        return $this;
    }

    public function getInvoiceEncodingFormat(): ?string
    {
        return $this->invoiceEncodingFormat;
    }

    public function setInvoiceEncodingFormat(?string $invoiceEncodingFormat): static
    {
        $this->invoiceEncodingFormat = $invoiceEncodingFormat;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function hasInvoice(): bool
    {
        return null !== $this->invoiceName;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }
}
