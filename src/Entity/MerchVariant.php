<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\MerchSize;
use App\Enum\SupportType;
use App\Repository\MerchVariantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * One size/colour of a Merch design (schema.org: Product, member of a ProductGroup).
 *
 * Each variant has its own stock, which is why it is the sellable Article and not the
 * design itself. Its name is derived from the design, size and colour — never typed in.
 */
#[ORM\Entity(repositoryClass: MerchVariantRepository::class)]
class MerchVariant extends Article
{
    #[ORM\ManyToOne(inversedBy: 'variants')]
    #[ORM\JoinColumn(nullable: true)]
    #[Assert\NotNull]
    private ?Merch $merch = null;

    #[ORM\Column(length: 20, nullable: true, enumType: MerchSize::class)]
    #[Assert\NotNull(message: 'Choisissez une taille.')]
    private ?MerchSize $size = null;

    /**
     * Merch has no catalogue section until the merch pages exist.
     */
    public function getSupportType(): ?SupportType
    {
        return null;
    }

    public function getFormatLabel(): string
    {
        return trim(($this->size?->label() ?? '').' '.($this->color ?? ''));
    }

    protected function getParentImages(): Collection
    {
        return $this->merch?->getImages() ?? new ArrayCollection();
    }

    /**
     * Keeps the name in sync; called by every setter it depends on and by Merch::setName().
     */
    public function refreshName(): void
    {
        $this->name = trim(\sprintf('%s - %s', $this->merch?->getName() ?? '', $this->getFormatLabel()), ' -');
    }

    public function getMerch(): ?Merch
    {
        return $this->merch;
    }

    public function setMerch(?Merch $merch): static
    {
        $this->merch = $merch;
        $this->refreshName();

        return $this;
    }

    public function getSize(): ?MerchSize
    {
        return $this->size;
    }

    public function setSize(?MerchSize $size): static
    {
        $this->size = $size;
        $this->refreshName();

        return $this;
    }

    public function setColor(?string $color): static
    {
        parent::setColor($color);
        $this->refreshName();

        return $this;
    }
}
