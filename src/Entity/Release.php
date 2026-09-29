<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\AlbumReleaseType;
use App\Enum\ReleaseFormat;
use App\Enum\SupportType;
use App\Repository\ReleaseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * One physical pressing of an Album (schema.org: MusicRelease + Product).
 *
 * Black vinyl and red vinyl of the same record are two Releases of one Album.
 * Columns are nullable in the database because of single table inheritance; the
 * constraints below are what makes them mandatory.
 */
#[ORM\Entity(repositoryClass: ReleaseRepository::class)]
class Release extends Article
{
    #[ORM\ManyToOne(inversedBy: 'releases')]
    #[ORM\JoinColumn(nullable: true)]
    #[Assert\NotNull(message: 'Choisissez un album.')]
    private ?Album $album = null;

    #[ORM\Column(length: 20, nullable: true, enumType: ReleaseFormat::class)]
    #[Assert\NotNull(message: 'Choisissez un format.')]
    private ?ReleaseFormat $format = null;

    /** Edition qualifier: "Réédition 2024", "Édition limitée", "Test pressing". */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $editionLabel = null;

    /** Number of copies pressed, for limited runs. */
    #[ORM\Column(nullable: true)]
    #[Assert\Positive]
    private ?int $limitedTo = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1900, max: 2100)]
    private ?int $pressingYear = null;

    /** KBR#012, or the reference of a third-party label. */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $catalogNumber = null;

    /** Label that pressed this release, when it is not ours (distro). */
    #[ORM\ManyToOne(inversedBy: 'releases')]
    private ?Label $label = null;

    public function getSupportType(): ?SupportType
    {
        if (null === $this->format) {
            return null;
        }

        return SupportType::forRelease($this->format, $this->album?->getReleaseType() ?? AlbumReleaseType::ALBUM);
    }

    public function getFormatLabel(): string
    {
        return $this->format?->label() ?? '';
    }

    protected function getParentImages(): Collection
    {
        return $this->album?->getImages() ?? new ArrayCollection();
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

    public function getFormat(): ?ReleaseFormat
    {
        return $this->format;
    }

    public function setFormat(?ReleaseFormat $format): static
    {
        $this->format = $format;

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

    public function getLimitedTo(): ?int
    {
        return $this->limitedTo;
    }

    public function setLimitedTo(?int $limitedTo): static
    {
        $this->limitedTo = $limitedTo;

        return $this;
    }

    public function getPressingYear(): ?int
    {
        return $this->pressingYear;
    }

    public function setPressingYear(?int $pressingYear): static
    {
        $this->pressingYear = $pressingYear;

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

    public function getLabel(): ?Label
    {
        return $this->label;
    }

    public function setLabel(?Label $label): static
    {
        $this->label = $label;

        return $this;
    }
}
