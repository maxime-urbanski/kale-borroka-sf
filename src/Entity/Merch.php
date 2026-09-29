<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MerchRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A merch design — "T-shirt Quartier Maudit" (schema.org: ProductGroup).
 *
 * Not sellable itself: sizes and colours are MerchVariants, each with its own stock.
 */
#[ORM\Entity(repositoryClass: MerchRepository::class)]
class Merch
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Donnez un nom au merch.')]
    private ?string $name = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Gedmo\Slug(fields: ['name'])]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $published = false;

    /** Band the merch is for; null for label merch. */
    #[ORM\ManyToOne(inversedBy: 'merches')]
    private ?Artist $artist = null;

    #[ORM\ManyToOne]
    private ?Category $category = null;

    /** @var Collection<int, MerchVariant> */
    #[ORM\OneToMany(mappedBy: 'merch', targetEntity: MerchVariant::class, cascade: ['persist'], orphanRemoval: true)]
    #[Assert\Valid]
    private Collection $variants;

    /** @var Collection<int, Image> */
    #[ORM\ManyToMany(targetEntity: Image::class)]
    #[ORM\JoinTable(name: 'merch_image')]
    private Collection $images;

    public function __construct()
    {
        $this->variants = new ArrayCollection();
        $this->images = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        foreach ($this->variants as $variant) {
            $variant->refreshName();
        }

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;

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

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): static
    {
        $this->published = $published;

        return $this;
    }

    public function getArtist(): ?Artist
    {
        return $this->artist;
    }

    public function setArtist(?Artist $artist): static
    {
        $this->artist = $artist;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, MerchVariant>
     */
    public function getVariants(): Collection
    {
        return $this->variants;
    }

    public function addVariant(MerchVariant $variant): static
    {
        if (!$this->variants->contains($variant)) {
            $this->variants->add($variant);
            $variant->setMerch($this);
        }

        return $this;
    }

    public function removeVariant(MerchVariant $variant): static
    {
        if ($this->variants->removeElement($variant) && $variant->getMerch() === $this) {
            $variant->setMerch(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Image>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(Image $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
        }

        return $this;
    }

    public function removeImage(Image $image): static
    {
        $this->images->removeElement($image);

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }
}
