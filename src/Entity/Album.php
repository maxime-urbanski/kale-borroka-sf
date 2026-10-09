<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\AlbumReleaseType;
use App\Repository\AlbumRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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

    /** Album, EP, split… Decides whether a 12" is filed under LP or EP (SupportType::forRelease()). */
    #[ORM\Column(length: 20, enumType: AlbumReleaseType::class, options: ['default' => 'album'])]
    private AlbumReleaseType $releaseType = AlbumReleaseType::ALBUM;

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
    #[Assert\Valid]
    private Collection $tracklists;

    /** @var Collection<int, Style> */
    #[ORM\ManyToMany(targetEntity: Style::class, inversedBy: 'albums', cascade: ['persist'])]
    private Collection $styles;

    #[ORM\ManyToOne(inversedBy: 'albums')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Artist $artist = null;

    /**
     * Pressings of this album. Created from the album form, hence the cascade.
     *
     * @var Collection<int, Release>
     */
    #[ORM\OneToMany(mappedBy: 'album', targetEntity: Release::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    #[Assert\Valid]
    private Collection $releases;

    /**
     * Uploaded from the album form ("Visuels" tab), hence the cascade. The first is the cover.
     *
     * @var Collection<int, Image>
     */
    #[ORM\ManyToMany(targetEntity: Image::class, mappedBy: 'album', cascade: ['persist'])]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    #[Assert\Valid]
    private Collection $images;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $kbrProductionId = null;

    public function __construct()
    {
        $this->labels = new ArrayCollection();
        $this->tracklists = new ArrayCollection();
        $this->styles = new ArrayCollection();
        $this->releases = new ArrayCollection();
        $this->images = new ArrayCollection();
    }

    /**
     * Sides (TracklistLayout) only mean something when every track has a position, once, in
     * track order: A1, A2, B1… A tracklist without any position is a CD's.
     */
    #[Assert\Callback]
    public function validateTracklistPositions(ExecutionContextInterface $context): void
    {
        $positions = $this->tracklists->map(static fn (Song $song): ?string => $song->getPosition())->getValues();
        $given = array_values(array_filter($positions, static fn (?string $position): bool => null !== $position));

        if ([] === $given) {
            return;
        }

        $twice = null;
        $seen = [];

        foreach ($given as $position) {
            if (isset($seen[$position])) {
                $twice ??= $position;
            }
            $seen[$position] = true;
        }

        $message = match (true) {
            \count($given) !== \count($positions) => 'Indiquez la face de tous les morceaux, ou d\'aucun.',
            null !== $twice => \sprintf('Position %s en double.', $twice),
            $given !== self::sortedPositions($given) => 'Les positions doivent suivre l\'ordre des morceaux (A1 avant A2…).',
            default => null,
        };

        if (null !== $message) {
            $context->buildViolation($message)->atPath('tracklists')->addViolation();
        }
    }

    /**
     * @param list<string> $positions
     *
     * @return list<string>
     */
    private static function sortedPositions(array $positions): array
    {
        // By side, then by number on it: A2 before A10.
        usort($positions, static fn (string $a, string $b): int => [$a[0], (int) substr($a, 1)] <=> [$b[0], (int) substr($b, 1)]);

        return $positions;
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

    public function getReleaseType(): AlbumReleaseType
    {
        return $this->releaseType;
    }

    public function setReleaseType(AlbumReleaseType $releaseType): static
    {
        $this->releaseType = $releaseType;

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
     * @return Collection<int, Release>
     */
    public function getReleases(): Collection
    {
        return $this->releases;
    }

    public function addRelease(Release $release): static
    {
        if (!$this->releases->contains($release)) {
            $this->releases->add($release);
            $release->setAlbum($this);
        }

        return $this;
    }

    public function removeRelease(Release $release): static
    {
        // set the owning side to null (unless already changed)
        if ($this->releases->removeElement($release) && $release->getAlbum() === $this) {
            $release->setAlbum(null);
        }

        return $this;
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

    public function getCoverImageName(): ?string
    {
        return Image::first($this->images)?->getImageName();
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
