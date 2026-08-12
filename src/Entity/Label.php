<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LabelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: LabelRepository::class)]
#[Vich\Uploadable]
class Label
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

    #[ORM\Column(nullable: true)]
    private ?bool $isFriend = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /** ISO 3166-1 alpha-2 country code. */
    #[ORM\Column(length: 2, nullable: true)]
    private ?string $country = null;

    /** Stored filename, written by VichUploader through the `labels` mapping. */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column(nullable: true)]
    private ?int $logoSize = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $logoUpdatedAt = null;

    #[Vich\UploadableField(mapping: 'labels', fileNameProperty: 'logo', size: 'logoSize')]
    private ?File $logoFile = null;

    /** @var Collection<int, Album> */
    #[ORM\ManyToMany(targetEntity: Album::class, mappedBy: 'labels', cascade: ['persist'])]
    private Collection $albums;

    public function __construct()
    {
        $this->albums = new ArrayCollection();
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

    public function isIsFriend(): ?bool
    {
        return $this->isFriend;
    }

    public function setIsFriend(?bool $isFriend): static
    {
        $this->isFriend = $isFriend;

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

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

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

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;

        return $this;
    }

    public function getLogoSize(): ?int
    {
        return $this->logoSize;
    }

    public function setLogoSize(?int $logoSize): static
    {
        $this->logoSize = $logoSize;

        return $this;
    }

    public function getLogoUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->logoUpdatedAt;
    }

    public function getLogoFile(): ?File
    {
        return $this->logoFile;
    }

    /**
     * Bumping logoUpdatedAt is what makes Doctrine see a changeset when only the
     * (non-persisted) file changes — same trick as Image::setImageFile().
     */
    public function setLogoFile(?File $logoFile = null): static
    {
        $this->logoFile = $logoFile;

        if (null !== $logoFile) {
            $this->logoUpdatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    /**
     * @return Collection<int, Album>
     */
    public function getAlbums(): Collection
    {
        return $this->albums;
    }

    public function addAlbum(Album $album): static
    {
        if (!$this->albums->contains($album)) {
            $this->albums->add($album);
            $album->addLabel($this);
        }

        return $this;
    }

    public function removeAlbum(Album $album): static
    {
        if ($this->albums->removeElement($album)) {
            $album->removeLabel($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
