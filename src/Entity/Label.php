<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LabelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LabelRepository::class)]
class Label
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /** Third-party label whose records we distribute. */
    #[ORM\Column(options: ['default' => false])]
    private bool $isDistro = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    /** @var Collection<int, Album> */
    #[ORM\ManyToMany(targetEntity: Album::class, mappedBy: 'labels', cascade: ['persist'])]
    private Collection $albums;

    /** @var Collection<int, Release> */
    #[ORM\OneToMany(mappedBy: 'label', targetEntity: Release::class)]
    private Collection $releases;

    public function __construct()
    {
        $this->albums = new ArrayCollection();
        $this->releases = new ArrayCollection();
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

    public function isDistro(): bool
    {
        return $this->isDistro;
    }

    public function setIsDistro(bool $isDistro): static
    {
        $this->isDistro = $isDistro;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;

        return $this;
    }

    /**
     * @return Collection<int, Release>
     */
    public function getReleases(): Collection
    {
        return $this->releases;
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
        return (string) $this->name;
    }
}
