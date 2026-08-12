<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\SupportType;
use App\Repository\SupportRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SupportRepository::class)]
class Support
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * Canonical format, typed with the SupportType enum.
     *
     * `name` stays the URL segment for backward compatibility; `code` is what the
     * application should branch on.
     */
    #[ORM\Column(length: 32, unique: true, enumType: SupportType::class)]
    private ?SupportType $code = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $icon = null;

    /** @var Collection<int, Edition> */
    #[ORM\OneToMany(mappedBy: 'support', targetEntity: Edition::class)]
    private Collection $editions;

    public function __construct()
    {
        $this->editions = new ArrayCollection();
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

    public function getCode(): ?SupportType
    {
        return $this->code;
    }

    public function setCode(SupportType $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;

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
            $edition->setSupport($this);
        }

        return $this;
    }

    public function removeEdition(Edition $edition): static
    {
        // set the owning side to null (unless already changed)
        if ($this->editions->removeElement($edition) && $edition->getSupport() === $this) {
            $edition->setSupport(null);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
