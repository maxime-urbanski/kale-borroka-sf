<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ItemAvailability;
use App\Enum\ItemCondition;
use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * A sellable offer on a given Edition (schema.org: Product + Offer).
 *
 * Everything editorial lives on Album, everything about the pressing on Edition; this
 * only holds what varies between two copies of the same pressing — price, stock,
 * condition. That is what makes "new 25 €" and "second-hand 15 €" two Articles of one
 * Edition.
 */
#[ORM\Entity(repositoryClass: ArticleRepository::class)]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'articles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Edition $edition = null;

    #[ORM\Column]
    private ?int $quantity = null;

    /** Price in cents. */
    #[ORM\Column]
    private ?int $price = null;

    /** Internal reference (schema.org: sku). */
    #[ORM\Column(length: 64, nullable: true, unique: true)]
    private ?string $sku = null;

    /** EAN-13 barcode (schema.org: gtin13). */
    #[ORM\Column(length: 13, nullable: true)]
    private ?string $gtin13 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /** Mapped to `item_condition`: `condition` is a reserved word in PostgreSQL. */
    #[ORM\Column(name: 'item_condition', length: 32, enumType: ItemCondition::class, options: ['default' => 'new'])]
    private ItemCondition $condition = ItemCondition::NEW;

    #[ORM\Column(length: 32, enumType: ItemAvailability::class, options: ['default' => 'in_stock'])]
    private ItemAvailability $availability = ItemAvailability::IN_STOCK;

    /** When a pre-order actually ships. */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $availableFrom = null;

    /** Shipping weight, in grams. */
    #[ORM\Column(nullable: true)]
    private ?int $weight = null;

    #[ORM\Column]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeImmutable $updatedAt = null;

    /** @var Collection<int, OrderDetails> */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: OrderDetails::class)]
    private Collection $orderDetails;

    public function __construct()
    {
        $this->orderDetails = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEdition(): ?Edition
    {
        return $this->edition;
    }

    public function setEdition(?Edition $edition): static
    {
        $this->edition = $edition;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(?string $sku): static
    {
        $this->sku = $sku;

        return $this;
    }

    public function getGtin13(): ?string
    {
        return $this->gtin13;
    }

    public function setGtin13(?string $gtin13): static
    {
        $this->gtin13 = $gtin13;

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

    public function getCondition(): ItemCondition
    {
        return $this->condition;
    }

    public function setCondition(ItemCondition $condition): static
    {
        $this->condition = $condition;

        return $this;
    }

    public function getAvailability(): ItemAvailability
    {
        return $this->availability;
    }

    public function setAvailability(ItemAvailability $availability): static
    {
        $this->availability = $availability;

        return $this;
    }

    public function getAvailableFrom(): ?\DateTimeInterface
    {
        return $this->availableFrom;
    }

    public function setAvailableFrom(?\DateTimeInterface $availableFrom): static
    {
        $this->availableFrom = $availableFrom;

        return $this;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function setWeight(?int $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, OrderDetails>
     */
    public function getOrderDetails(): Collection
    {
        return $this->orderDetails;
    }

    public function addOrderDetail(OrderDetails $orderDetail): static
    {
        if (!$this->orderDetails->contains($orderDetail)) {
            $this->orderDetails->add($orderDetail);
            $orderDetail->setProduct($this);
        }

        return $this;
    }

    public function removeOrderDetail(OrderDetails $orderDetail): static
    {
        if ($this->orderDetails->removeElement($orderDetail)) {
            // set the owning side to null (unless already changed)
            if ($orderDetail->getProduct() === $this) {
                $orderDetail->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * Read-only shortcuts through the edition. The cart, the order history, the wishlist
     * and the collection all deal in Articles but need to display the record itself.
     */
    public function getAlbum(): ?Album
    {
        return $this->edition?->getAlbum();
    }

    public function getSupport(): ?Support
    {
        return $this->edition?->getSupport();
    }

    public function getCoverImage(): ?Image
    {
        return $this->edition?->getCoverImage();
    }

    /**
     * Human-readable label: "Brixton Cats - Quartier Maudit — LP vinyle rouge", suffixed
     * with the condition when it is not a new copy.
     */
    public function getName(): string
    {
        $name = (string) $this->edition?->fullName();

        if (ItemCondition::NEW !== $this->condition) {
            $name .= ' ('.$this->condition->label().')';
        }

        return $name;
    }

    /**
     * @return array<string, string|null>
     */
    public function getRouteParams(): array
    {
        return $this->edition?->getRouteParams() ?? [];
    }

    public function isPurchasable(): bool
    {
        return $this->availability->isPurchasable() && $this->quantity > 0;
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}
