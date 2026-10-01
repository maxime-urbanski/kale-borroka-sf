<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SongRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SongRepository::class)]
class Song
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $track = null;

    /**
     * Side and place on it as printed on a vinyl or a tape (A1, B3, C2 for a double LP's second
     * disc). Null on a CD, which plays straight through: the track number is enough.
     */
    #[ORM\Column(length: 3, nullable: true)]
    #[Assert\Regex(pattern: '/^[A-Z][1-9]\d?$/', message: 'Position : une lettre de face puis un numéro (A1, B3).')]
    private ?string $position = null;

    /** Length in seconds (schema.org: duration, rendered as ISO 8601). */
    #[ORM\Column(nullable: true)]
    private ?int $duration = null;

    /** @var Collection<int, Album> */
    #[ORM\ManyToMany(targetEntity: Album::class, mappedBy: 'tracklists', cascade: ['persist'])]
    private Collection $albums;

    /** @var Collection<int, Artist> */
    #[ORM\ManyToMany(targetEntity: Artist::class, inversedBy: 'songs')]
    private Collection $artist;

    public function __construct()
    {
        $this->albums = new ArrayCollection();
        $this->artist = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    /**
     * Typed « a1 » or « A1 »: the same position.
     */
    public function setPosition(?string $position): static
    {
        $position = null === $position ? '' : mb_strtoupper(trim($position));
        $this->position = '' === $position ? null : $position;

        return $this;
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

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getTrack(): ?int
    {
        return $this->track;
    }

    public function setTrack(int $track): static
    {
        $this->track = $track;

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
            $album->addTracklist($this);
        }

        return $this;
    }

    public function removeAlbum(Album $album): static
    {
        if ($this->albums->removeElement($album)) {
            $album->removeTracklist($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Artist>
     */
    public function getArtist(): Collection
    {
        return $this->artist;
    }

    public function addArtist(Artist $artist): static
    {
        if (!$this->artist->contains($artist)) {
            $this->artist->add($artist);
        }

        return $this;
    }

    public function removeArtist(Artist $artist): static
    {
        $this->artist->removeElement($artist);

        return $this;
    }

    public function __toString(): string
    {
        return $this->track.' - '.$this->name;
    }
}
