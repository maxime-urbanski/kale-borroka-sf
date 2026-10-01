<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ArtistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ArtistRepository::class)]
class Artist
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
    private ?string $description = null;

    /** ISO 3166-1 alpha-2 country code. */
    #[ORM\Column(length: 2, nullable: true)]
    #[Assert\Country(message: 'Pays inconnu : code à deux lettres attendu (FR, DE, ES…).')]
    private ?string $country = null;

    /**
     * Official pages — Bandcamp, Instagram, website… (schema.org: sameAs).
     *
     * @var list<string>
     */
    #[ORM\Column(type: Types::JSON, options: ['default' => '[]'])]
    #[Assert\All([new Assert\Url(message: 'Adresse invalide : elle doit commencer par https://', requireTld: true)])]
    private array $links = [];

    /** @var Collection<int, Album> */
    #[ORM\OneToMany(mappedBy: 'artist', targetEntity: Album::class)]
    private Collection $albums;

    /** @var Collection<int, Song> */
    #[ORM\ManyToMany(targetEntity: Song::class, mappedBy: 'artist')]
    private Collection $songs;

    /** @var Collection<int, Merch> */
    #[ORM\OneToMany(mappedBy: 'artist', targetEntity: Merch::class)]
    private Collection $merches;

    public function __construct()
    {
        $this->albums = new ArrayCollection();
        $this->songs = new ArrayCollection();
        $this->merches = new ArrayCollection();
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

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    /**
     * @param array<string> $links
     */
    public function setLinks(array $links): static
    {
        $this->links = array_values(array_filter(array_map('trim', $links), static fn (string $link): bool => '' !== $link));

        return $this;
    }

    /**
     * @return Collection<int, Merch>
     */
    public function getMerches(): Collection
    {
        return $this->merches;
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
            $album->setArtist($this);
        }

        return $this;
    }

    public function removeAlbum(Album $album): static
    {
        if ($this->albums->removeElement($album)) {
            // set the owning side to null (unless already changed)
            if ($album->getArtist() === $this) {
                $album->setArtist(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Song>
     */
    public function getSongs(): Collection
    {
        return $this->songs;
    }

    public function addSong(Song $song): static
    {
        if (!$this->songs->contains($song)) {
            $this->songs->add($song);
            $song->addArtist($this);
        }

        return $this;
    }

    public function removeSong(Song $song): static
    {
        if ($this->songs->removeElement($song)) {
            $song->removeArtist($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }
}
