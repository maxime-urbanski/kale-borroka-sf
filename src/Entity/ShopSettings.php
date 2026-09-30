<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ShopSettingsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Shop-wide settings editable from the back office. A single row; read it through
 * ShopSettingsProviderInterface, which falls back to defaults when the row is missing.
 */
#[ORM\Entity(repositoryClass: ShopSettingsRepository::class)]
class ShopSettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    /** At or below this stock, an article is flagged in the back office. */
    #[ORM\Column(options: ['default' => 2])]
    #[Assert\PositiveOrZero]
    private int $lowStockThreshold = 2;

    /** Order total (cents) from which shipping is free; null = never. */
    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $freeShippingThreshold = null;

    /**
     * Funds of the label on openingBalanceDate (cents, may be negative): the label existed before
     * the shop. Shop payments, event sales and expenses from that day on are added to it.
     */
    #[ORM\Column(options: ['default' => 0])]
    private int $openingBalance = 0;

    /** Null: every movement ever recorded counts. */
    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $openingBalanceDate = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Email]
    private ?string $contactEmail = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLowStockThreshold(): int
    {
        return $this->lowStockThreshold;
    }

    public function setLowStockThreshold(int $lowStockThreshold): static
    {
        $this->lowStockThreshold = $lowStockThreshold;

        return $this;
    }

    public function getFreeShippingThreshold(): ?int
    {
        return $this->freeShippingThreshold;
    }

    public function setFreeShippingThreshold(?int $freeShippingThreshold): static
    {
        $this->freeShippingThreshold = $freeShippingThreshold;

        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): static
    {
        $this->contactEmail = $contactEmail;

        return $this;
    }

    public function getOpeningBalance(): int
    {
        return $this->openingBalance;
    }

    public function setOpeningBalance(?int $openingBalance): static
    {
        $this->openingBalance = $openingBalance ?? 0;

        return $this;
    }

    public function getOpeningBalanceDate(): ?\DateTimeImmutable
    {
        return $this->openingBalanceDate;
    }

    public function setOpeningBalanceDate(?\DateTimeImmutable $openingBalanceDate): static
    {
        $this->openingBalanceDate = $openingBalanceDate;

        return $this;
    }
}
