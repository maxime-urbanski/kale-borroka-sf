<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\AlbumProductionType;
use App\Repository\AlbumRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: AlbumRepository::class)]
class Album
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Gedmo\Slug(fields: ['name'])]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(length: 32, nullable: true, enumType: AlbumProductionType::class)]
    private ?AlbumProductionType $productionType = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $recordingYear = null;

    /** ISO 3166-1 alpha-2 country code. */
    #[ORM\Column(length: 2, nullable: true)]
    private ?string $countryOfOrigin = null;

    /** Total running time, in seconds. */
    #[ORM\Column(nullable: true)]
    private ?int $duration = null;

    #[ORM\Column]
    private ?bool $kbrProduction = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $folder = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_release = null;

    /** @var Collection<int, Label> */
    #[ORM\ManyToMany(targetEntity: Label::class, inversedBy: 'albums', cascade: ['persist'])]
    private Collection $labels;

    /** @var Collection<int, Song> */
    #[ORM\ManyToMany(targetEntity: Song::class, inversedBy: 'albums', cascade: ['persist'])]
    #[ORM\OrderBy(['track' => 'ASC'])]
    private Collection $tracklists;

    /** @var Collection<int, Style> */
    #[ORM\ManyToMany(targetEntity: Style::class, inversedBy: 'albums', cascade: ['persist'])]
    private Collection $styles;

    #[ORM\ManyToOne(inversedBy: 'albums')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Artist $artist = null;

    /** @var Collection<int, Edition> */
    #[ORM\OneToMany(mappedBy: 'album', targetEntity: Edition::class, orphanRemoval: true)]
    private Collection $editions;

    /** @var Collection<int, Image> */
    #[ORM\ManyToMany(targetEntity: Image::class, mappedBy: 'album')]
    private Collection $images;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $kbrProductionId = null;

    public function __construct()
    {
        $this->labels = new ArrayCollection();
        $this->tracklists = new ArrayCollection();
        $this->styles = new ArrayCollection();
        $this->editions = new ArrayCollection();
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

    public function getProductionType(): ?AlbumProductionType
    {
        return $this->productionType;
    }

    public function setProductionType(?AlbumProductionType $productionType): static
    {
        $this->productionType = $productionType;

        return $this;
    }

    public function getRecordingYear(): ?int
    {
        return $this->recordingYear;
    }

    public function setRecordingYear(?int $recordingYear): static
    {
        $this->recordingYear = $recordingYear;

        return $this;
    }

    public function getCountryOfOrigin(): ?string
    {
        return $this->countryOfOrigin;
    }

    public function setCountryOfOrigin(?string $countryOfOrigin): static
    {
        $this->countryOfOrigin = $countryOfOrigin;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function isKbrProduction(): ?bool
    {
        return $this->kbrProduction;
    }

    public function setKbrProduction(bool $kbrProduction): static
    {
        $this->kbrProduction = $kbrProduction;

        return $this;
    }

    public function getFolder(): ?string
    {
        return $this->folder;
    }

    public function setFolder(?string $folder): static
    {
        $this->folder = $folder;

        return $this;
    }

    public function getDateRelease(): ?\DateTimeInterface
    {
        return $this->date_release;
    }

    public function setDateRelease(?\DateTimeInterface $date_release): static
    {
        $this->date_release = $date_release;

        return $this;
    }

    /**
     * @return Collection<int, Label>
     */
    public function getLabels(): Collection
    {
        return $this->labels;
    }

    public function addLabel(Label $label): static
    {
        if (!$this->labels->contains($label)) {
            $this->labels->add($label);
        }

        return $this;
    }

    public function removeLabel(Label $label): static
    {
        $this->labels->removeElement($label);

        return $this;
    }

    /**
     * @return Collection<int, Song>
     */
    public function getTracklists(): Collection
    {
        return $this->tracklists;
    }

    public function addTracklist(Song $tracklist): static
    {
        if (!$this->tracklists->contains($tracklist)) {
            $this->tracklists->add($tracklist);
        }

        return $this;
    }

    public function removeTracklist(Song $tracklist): static
    {
        $this->tracklists->removeElement($tracklist);

        return $this;
    }

    /**
     * @return Collection<int, Style>
     */
    public function getStyles(): Collection
    {
        return $this->styles;
    }

    public function addStyle(Style $style): static
    {
        if (!$this->styles->contains($style)) {
            $this->styles->add($style);
        }

        return $this;
    }

    public function removeStyle(Style $style): static
    {
        $this->styles->removeElement($style);

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

    /**
     * @return Collection<int, Edition>
     */
    public function getEditions(): Collection
    {
        return $this->editions;
    }

    public function addEdition(Edition $edition): static
    {
        if (!$this->editions->contains($edition)) {
            $this->editions->add($edition);
            $edition->setAlbum($this);
        }

        return $this;
    }

    public function removeEdition(Edition $edition): static
    {
        if ($this->editions->removeElement($edition) && $edition->getAlbum() === $this) {
            $edition->setAlbum(null);
        }

        return $this;
    }

    /**
     * Editions of this album, optionally narrowed to one support — the catalogue is
     * browsed per support, so /catalog/lp must only price and picture the LP pressings.
     *
     * @return Collection<int, Edition>
     */
    public function getEditionsForSupport(?Support $support = null): Collection
    {
        if (null === $support) {
            return $this->editions;
        }

        return $this->editions->filter(
            static fn (Edition $edition): bool => $edition->getSupport() === $support
        );
    }

    /**
     * The edition a catalogue card stands for: the cheapest one still purchasable, or
     * failing that the first, so a sold-out record is still displayed.
     */
    public function getPreviewEdition(?Support $support = null): ?Edition
    {
        $editions = $this->getEditionsForSupport($support);

        $withOffer = $editions->filter(
            static fn (Edition $edition): bool => null !== $edition->getCheapestArticle()
        );

        $candidates = $withOffer->isEmpty() ? $editions : $withOffer;

        if ($candidates->isEmpty()) {
            return null;
        }

        $sorted = $candidates->toArray();
        usort($sorted, static fn (Edition $a, Edition $b): int => ($a->getCheapestArticle()?->getPrice() ?? PHP_INT_MAX)
            <=> ($b->getCheapestArticle()?->getPrice() ?? PHP_INT_MAX));

        return $sorted[0];
    }

    public function getCheapestArticle(?Support $support = null): ?Article
    {
        return $this->getPreviewEdition($support)?->getCheapestArticle();
    }

    /**
     * True when the card should advertise "from X €" rather than a single price: either
     * several pressings, or several offers on the one pressing.
     */
    public function hasSeveralOffers(?Support $support = null): bool
    {
        $editions = $this->getEditionsForSupport($support);

        if ($editions->count() > 1) {
            return true;
        }

        return $this->getPreviewEdition($support)?->hasSeveralOffers() ?? false;
    }

    public function getCoverImage(?Support $support = null): ?Image
    {
        return $this->getPreviewEdition($support)?->getCoverImage()
            ?: ($this->images->first() ?: null);
    }

    public function fullName(): string
    {
        return $this->artist.' - '.$this->name;
    }

    public function __toString(): string
    {
        return $this->fullName();
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
            $image->addAlbum($this);
        }

        return $this;
    }

    public function removeImage(Image $image): static
    {
        if ($this->images->removeElement($image)) {
            $image->removeAlbum($this);
        }

        return $this;
    }

    public function getKbrProductionId(): ?string
    {
        return $this->kbrProductionId;
    }

    public function setKbrProductionId(?string $kbrProductionId): static
    {
        $this->kbrProductionId = $kbrProductionId;

        return $this;
    }
}
