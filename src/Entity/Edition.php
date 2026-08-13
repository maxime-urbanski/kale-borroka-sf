<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EditionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sluggable\Handler\RelativeSlugHandler;

/**
 * A physical pressing of an Album (schema.org: MusicRelease).
 *
 * This is the variant axis: black vinyl and red vinyl of the same record are two Editions
 * of one Album. It carries the format and everything that distinguishes one pressing from
 * another; money and stock live one level down, on Article.
 */
#[ORM\Entity(repositoryClass: EditionRepository::class)]
class Edition
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    /** Short qualifier shown in the edition picker — "LP", "LP vinyle rouge", "CD digipack". */
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * Slugs are prefixed with the album's own slug, so two albums can both have an "LP"
     * edition without colliding: quartier-maudit-lp, boots-n-booze-lp.
     */
    #[ORM\Column(length: 255, unique: true)]
    #[Gedmo\Slug(fields: ['name'])]
    // SlugHandler must be its own property attribute: Slug::$handlers is deprecated since
    // gedmo 3.18 and the attribute driver reads only the standalone one.
    #[Gedmo\SlugHandler(class: RelativeSlugHandler::class, options: [
        'relationField' => 'album',
        'relationSlugField' => 'slug',
        'separator' => '-',
    ])]
    private ?string $slug = null;

    #[ORM\ManyToOne(inversedBy: 'editions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Album $album = null;

    #[ORM\ManyToOne(inversedBy: 'editions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Support $support = null;

    /** Vinyl colour, free text: "rouge", "splatter vert/noir", … */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $color = null;

    /** Edition qualifier: "Collector", "Édition limitée", "Réédition 2024". */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $editionLabel = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $catalogNumber = null;

    /** Number of copies pressed, for limited runs. */
    #[ORM\Column(nullable: true)]
    private ?int $pressingRun = null;

    /** Release date of this pressing, which can differ from the album's. */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $releaseDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /** @var Collection<int, Image> */
    #[ORM\ManyToMany(targetEntity: Image::class, mappedBy: 'editions')]
    private Collection $images;

    /** @var Collection<int, Article> */
    #[ORM\OneToMany(mappedBy: 'edition', targetEntity: Article::class, orphanRemoval: true)]
    private Collection $articles;

    public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->articles = new ArrayCollection();
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

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getAlbum(): ?Album
    {
        return $this->album;
    }

    public function setAlbum(?Album $album): static
    {
        $this->album = $album;

        return $this;
    }

    public function getSupport(): ?Support
    {
        return $this->support;
    }

    public function setSupport(?Support $support): static
    {
        $this->support = $support;

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

    public function getEditionLabel(): ?string
    {
        return $this->editionLabel;
    }

    public function setEditionLabel(?string $editionLabel): static
    {
        $this->editionLabel = $editionLabel;

        return $this;
    }

    public function getCatalogNumber(): ?string
    {
        return $this->catalogNumber;
    }

    public function setCatalogNumber(?string $catalogNumber): static
    {
        $this->catalogNumber = $catalogNumber;

        return $this;
    }

    public function getPressingRun(): ?int
    {
        return $this->pressingRun;
    }

    public function setPressingRun(?int $pressingRun): static
    {
        $this->pressingRun = $pressingRun;

        return $this;
    }

    public function getReleaseDate(): ?\DateTimeInterface
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(?\DateTimeInterface $releaseDate): static
    {
        $this->releaseDate = $releaseDate;

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
            $image->addEdition($this);
        }

        return $this;
    }

    public function removeImage(Image $image): static
    {
        if ($this->images->removeElement($image)) {
            $image->removeEdition($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Article>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(Article $article): static
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
            $article->setEdition($this);
        }

        return $this;
    }

    public function removeArticle(Article $article): static
    {
        if ($this->articles->removeElement($article) && $article->getEdition() === $this) {
            $article->setEdition(null);
        }

        return $this;
    }

    /**
     * Falls back to the album's artwork: most editions reuse the same sleeve, only the
     * ones photographed separately (a coloured pressing) carry their own image.
     */
    public function getCoverImage(): ?Image
    {
        return $this->images->first() ?: $this->album?->getImages()->first() ?: null;
    }

    /**
     * Cheapest purchasable offer, which is what the catalogue card advertises.
     */
    public function getCheapestArticle(): ?Article
    {
        $purchasable = $this->articles
            ->filter(static fn (Article $article): bool => $article->isPurchasable())
            ->toArray();

        if ([] === $purchasable) {
            return null;
        }

        usort($purchasable, static fn (Article $a, Article $b): int => $a->getPrice() <=> $b->getPrice());

        return $purchasable[0];
    }

    public function hasSeveralOffers(): bool
    {
        return $this->articles->count() > 1;
    }

    /**
     * Route parameters for app_catalog_show. The album page is keyed on the album slug;
     * `edition` picks which pressing is pre-selected.
     *
     * @return array<string, string|null>
     */
    public function getRouteParams(): array
    {
        return [
            'support' => $this->support?->getName(),
            'slug' => $this->album?->getSlug(),
            'edition' => $this->slug,
        ];
    }

    public function fullName(): string
    {
        return implode(' — ', array_filter([$this->album?->fullName(), $this->name]));
    }

    /**
     * Used as the header of each row in the album's Éditions collection, which is rendered
     * before anything has been typed — hence the fallback.
     */
    public function __toString(): string
    {
        return $this->fullName() ?: 'Nouvelle édition';
    }
}
