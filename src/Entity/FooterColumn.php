<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\FooterColumnRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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
     * Every catalogue section (Support) first, read live: a section created later, by
     * app:catalog:import for instance, is listed without a link of its own.
     */
    #[ORM\Column(options: ['default' => false])]
    private bool $supportsListed = false;

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

    /**
     * A section listed by the column and linked again would show twice.
     */
    #[Assert\Callback]
    public function validateSections(ExecutionContextInterface $context): void
    {
        if ($this->supportsListed && $this->links->exists(static fn (int $key, FooterLink $link): bool => null !== $link->getSupport())) {
            $context->buildViolation('La colonne liste déjà tous les rayons : retirez les liens vers un rayon, ou décochez « Tous les rayons du catalogue ».')
                ->atPath('supportsListed')
                ->addViolation();
        }
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

    public function isSupportsListed(): bool
    {
        return $this->supportsListed;
    }

    public function setSupportsListed(bool $supportsListed): static
    {
        $this->supportsListed = $supportsListed;

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
     * @return list<FooterLink> the links a visitor can follow, but those to a section the
     *                          column already lists (refused by the form, not by every writer)
     */
    public function getShownLinks(): array
    {
        return array_values($this->links->filter(
            fn (FooterLink $link): bool => $link->isShown() && !($this->supportsListed && null !== $link->getSupport()),
        )->toArray());
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
