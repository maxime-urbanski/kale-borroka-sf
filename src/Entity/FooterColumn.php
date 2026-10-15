<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\FooterColumnRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A column of links in the footer (« À propos », « Shop »…), created from the back office
 * (« Colonnes du footer »). Schema.org: SiteNavigationElement (name, position).
 */
#[ORM\Entity(repositoryClass: FooterColumnRepository::class)]
class FooterColumn
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 60)]
    #[Assert\NotBlank(message: 'Donnez un titre à la colonne.', normalizer: 'trim')]
    #[Assert\Length(max: 60)]
    private string $name = '';

    /** Order in the footer, smallest first, after the brand. */
    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    /**
     * @var Collection<int, FooterLink>
     */
    #[ORM\OneToMany(targetEntity: FooterLink::class, mappedBy: 'column', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    #[Assert\Valid]
    private Collection $links;

    public function __construct()
    {
        $this->links = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = (string) $name;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = (int) $position;

        return $this;
    }

    /**
     * @return Collection<int, FooterLink>
     */
    public function getLinks(): Collection
    {
        return $this->links;
    }

    /**
     * @return list<FooterLink> the links a visitor can follow; none: the column is not shown
     */
    public function getShownLinks(): array
    {
        return array_values($this->links->filter(static fn (FooterLink $link): bool => $link->isShown())->toArray());
    }

    public function addLink(FooterLink $link): static
    {
        if (!$this->links->contains($link)) {
            $this->links->add($link);
            $link->setColumn($this);
        }

        return $this;
    }

    public function removeLink(FooterLink $link): static
    {
        $this->links->removeElement($link);

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
