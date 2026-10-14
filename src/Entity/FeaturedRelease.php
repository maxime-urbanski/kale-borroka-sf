<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\FeatureKind;
use App\Repository\FeaturedReleaseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The record put forward at the top of the homepage: a pre-order, a favourite, a record to
 * discover (schema.org: WebPageElement about a MusicRelease; `release` ≈ about,
 * `releaseDate` from Product, `datePublished` / `expires` bound the display).
 *
 * Several can be prepared: the homepage shows the latest online one within its dates
 * (FeaturedReleaseRepository::findCurrent()).
 */
#[ORM\Entity(repositoryClass: FeaturedReleaseRepository::class)]
class FeaturedRelease
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, enumType: FeatureKind::class)]
    private FeatureKind $kind = FeatureKind::PREORDER;

    /** Deleting the release deletes its features. */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Choisissez le disque à mettre en avant.')]
    private ?Release $release = null;

    /** Line above the title; FeatureKind::defaultHeadline() when empty. */
    #[ORM\Column(length: 120, nullable: true)]
    #[Assert\Length(max: 120)]
    private ?string $headline = null;

    /** Plain text; the album's note when empty. */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /** Day the record comes out, printed on the pre-order stamp. */
    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $releaseDate = null;

    /** Where to listen to it (Bandcamp…). */
    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(message: 'Adresse invalide : elle doit commencer par http:// ou https://.', protocols: ['http', 'https'], requireTld: true)]
    #[Assert\Length(max: 255)]
    private ?string $listenUrl = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $published = false;

    /** Shown from this moment; null: as soon as it is online. */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $datePublished = null;

    /** Hidden from this moment; null: until it is taken offline. */
    #[ORM\Column(nullable: true)]
    #[Assert\GreaterThan(propertyPath: 'datePublished', message: 'La fin de l\'affichage doit suivre son début.')]
    private ?\DateTimeImmutable $expires = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKind(): FeatureKind
    {
        return $this->kind;
    }

    public function setKind(FeatureKind $kind): static
    {
        $this->kind = $kind;

        return $this;
    }

    public function getRelease(): ?Release
    {
        return $this->release;
    }

    public function setRelease(?Release $release): static
    {
        $this->release = $release;

        return $this;
    }

    public function getHeadline(): ?string
    {
        return $this->headline;
    }

    public function setHeadline(?string $headline): static
    {
        $this->headline = $headline;

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

    public function getReleaseDate(): ?\DateTimeImmutable
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(?\DateTimeImmutable $releaseDate): static
    {
        $this->releaseDate = $releaseDate;

        return $this;
    }

    public function getListenUrl(): ?string
    {
        return $this->listenUrl;
    }

    public function setListenUrl(?string $listenUrl): static
    {
        $this->listenUrl = $listenUrl;

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

    public function getDatePublished(): ?\DateTimeImmutable
    {
        return $this->datePublished;
    }

    public function setDatePublished(?\DateTimeImmutable $datePublished): static
    {
        $this->datePublished = $datePublished;

        return $this;
    }

    public function getExpires(): ?\DateTimeImmutable
    {
        return $this->expires;
    }

    public function setExpires(?\DateTimeImmutable $expires): static
    {
        $this->expires = $expires;

        return $this;
    }

    public function __toString(): string
    {
        return $this->kind->label().' : '.$this->release;
    }
}
