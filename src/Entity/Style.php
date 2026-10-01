<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StyleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A music style, picked from a closed list (OFFICIAL, after Discogs' styles, cut down to the
 * label's scene and without genres above them). The rows are seeded by a migration: adding a
 * style means adding it here, to a migration and to fixtures/style.yml (OfficialStylesTest).
 */
#[ORM\Entity(repositoryClass: StyleRepository::class)]
class Style
{
    public const array OFFICIAL = [
        'Punk',
        'Oi',
        'Street Punk',
        'Hardcore',
        'Crust',
        'Anarcho-Punk',
        'Celtic Punk',
        'Ska',
        'Ska Punk',
        'Two Tone',
        'Rocksteady',
        'Reggae',
        'Dub',
        'Rock & Roll',
        'Rockabilly',
        'Psychobilly',
        'Garage Rock',
        'Folk',
        'Folk Punk',
        'Chanson',
        'Hip Hop',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $name = null;

    /** @var Collection<int, Album> */
    #[ORM\ManyToMany(targetEntity: Album::class, mappedBy: 'styles', cascade: ['persist'])]
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
            $album->addStyle($this);
        }

        return $this;
    }

    public function removeAlbum(Album $album): static
    {
        if ($this->albums->removeElement($album)) {
            $album->removeStyle($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }
}
