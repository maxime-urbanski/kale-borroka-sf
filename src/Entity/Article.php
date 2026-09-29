<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ItemCondition;
use App\Enum\SupportType;
use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Anything that can be put in the cart (schema.org: Product + Offer).
 *
 * Single table inheritance: the cart, orders, wishlists and collections all point at this
 * root, so they do not care whether the item is a record, a fanzine or a t-shirt.
 * Doctrine cannot reach subclass fields from a query on this root: catalogue queries
 * that filter on album, format… go through ReleaseRepository / BookRepository.
 */
#[ORM\Entity(repositoryClass: ArticleRepository::class)]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string', length: 20)]
#[ORM\DiscriminatorMap(['release' => Release::class, 'book' => Book::class, 'merch' => MerchVariant::class])]
#[ORM\HasLifecycleCallbacks]
abstract class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    protected ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Donnez un nom à l\'article.')]
    protected ?string $name = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Gedmo\Slug(fields: ['name'])]
    protected ?string $slug = null;

    /** Internal reference (schema.org: sku). Generated on insert when left blank. */
    #[ORM\Column(length: 64, unique: true)]
    protected ?string $sku = null;

    /** EAN-13 / UPC barcode (schema.org: gtin13). */
    #[ORM\Column(length: 14, unique: true, nullable: true)]
    #[Assert\Regex(pattern: '/^\d{8,14}$/', message: 'Un code-barres ne contient que 8 à 14 chiffres.')]
    protected ?string $gtin = null;

    /** Price in cents. */
    #[ORM\Column]
    #[Assert\NotNull(message: 'Indiquez un prix.')]
    #[Assert\PositiveOrZero]
    protected ?int $price = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Indiquez un stock.')]
    #[Assert\PositiveOrZero]
    protected ?int $stock = null;

    /** Free text (schema.org: color): "noir", "rouge translucide", "splatter vert/noir"… */
    #[ORM\Column(length: 100, nullable: true)]
    protected ?string $color = null;

    /** Mapped to `item_condition`: `condition` is a reserved word in PostgreSQL. */
    #[ORM\Column(name: 'item_condition', length: 20, enumType: ItemCondition::class, options: ['default' => 'new'])]
    protected ItemCondition $itemCondition = ItemCondition::NEW;

    /** Unpublished articles (drafts, duplicates being edited) never show up in the shop. */
    #[ORM\Column(options: ['default' => false])]
    protected bool $published = false;

    #[ORM\Column]
    #[Gedmo\Timestampable(on: 'create')]
    protected ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Gedmo\Timestampable(on: 'update')]
    protected ?\DateTimeImmutable $updatedAt = null;

    /**
     * Pictures of this very item. When empty, the parent's pictures (album, merch design) are used.
     *
     * @var Collection<int, Image>
     */
    #[ORM\ManyToMany(targetEntity: Image::class)]
    #[ORM\JoinTable(name: 'article_image')]
    protected Collection $images;

    /** @var Collection<int, OrderDetails> */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: OrderDetails::class)]
    protected Collection $orderDetails;

    public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->orderDetails = new ArrayCollection();
    }

    /**
     * Catalogue section this article is listed under, and the `{support}` segment of its URL.
     * Null when it has no catalogue page (yet).
     */
    abstract public function getSupportType(): ?SupportType;

    /**
     * Short format tag shown next to the price: "Vinyle 12"", "Fanzine", "T-shirt M".
     */
    abstract public function getFormatLabel(): string;

    /**
     * @return Collection<int, Image>
     */
    abstract protected function getParentImages(): Collection;

    #[ORM\PrePersist]
    public function generateSku(): void
    {
        if (null === $this->sku || '' === $this->sku) {
            $this->sku = 'KBR-'.strtoupper(bin2hex(random_bytes(4)));
        }
    }

    public function getCoverImage(): ?Image
    {
        return $this->images->first() ?: ($this->getParentImages()->first() ?: null);
    }

    public function getCoverImageName(): ?string
    {
        return $this->getCoverImage()?->getImageName();
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

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(?string $sku): static
    {
        $this->sku = $sku;

        return $this;
    }

    public function getGtin(): ?string
    {
        return $this->gtin;
    }

    public function setGtin(?string $gtin): static
    {
        $this->gtin = $gtin;

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

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getItemCondition(): ItemCondition
    {
        return $this->itemCondition;
    }

    public function setItemCondition(ItemCondition $itemCondition): static
    {
        $this->itemCondition = $itemCondition;

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
     * A duplicate is a new, unpublished draft with no stock and no identifiers of its own:
     * slug and SKU are regenerated on insert, the barcode has to be typed in again.
     * Pictures are shared with the original.
     */
    public function __clone()
    {
        $this->id = null;
        $this->slug = null;
        $this->sku = null;
        $this->gtin = null;
        $this->stock = 0;
        $this->published = false;
        $this->createdAt = null;
        $this->updatedAt = null;
        $this->images = new ArrayCollection($this->images->toArray());
        $this->orderDetails = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }
}
