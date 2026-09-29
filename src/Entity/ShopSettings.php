<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ShopSettingsRepository;
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
}
