<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * A link of a footer column, to a CMS page, a catalogue section or an address. Edited inside
 * its column. Schema.org: SiteNavigationElement (name, url, position).
 */
#[ORM\Entity]
class FooterLink
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'links')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?FooterColumn $column = null;

    /** Link text; empty: the page's title or the section's name. */
    #[ORM\Column(length: 60, nullable: true)]
    #[Assert\Length(max: 60)]
    private ?string $name = null;

    /** Not shown while unpublished. */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?Page $page = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?Support $support = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Assert\Regex(pattern: Homepage::LINK_PATTERN, message: 'Une adresse commençant par https:// ou une page du site commençant par / (sans espace).')]
    private ?string $url = null;

    /** Order within the column, smallest first. */
    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    #[Assert\Callback]
    public function validateTarget(ExecutionContextInterface $context): void
    {
        $targets = \count(array_filter([$this->page, $this->support, $this->url], static fn (mixed $target): bool => null !== $target));

        if (1 !== $targets) {
            $context->buildViolation(0 === $targets ? 'Choisissez où mène le lien : une page, un rayon ou une adresse.' : 'Un lien mène à une seule destination : une page, un rayon ou une adresse.')
                ->atPath('page')
                ->addViolation();
        }

        if (null !== $this->url && null === $this->name) {
            $context->buildViolation('Donnez un texte au lien vers une adresse.')
                ->atPath('name')
                ->addViolation();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getColumn(): ?FooterColumn
    {
        return $this->column;
    }

    public function setColumn(?FooterColumn $column): static
    {
        $this->column = $column;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = null === $name || '' === trim($name) ? null : $name;

        return $this;
    }

    public function getPage(): ?Page
    {
        return $this->page;
    }

    public function setPage(?Page $page): static
    {
        $this->page = $page;

        return $this;
    }

    public function getSupport(): ?Support
    {
        return $this->support;
    }

    public function setSupport(?Support $support): static
    {
        $this->support = $support;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = null === $url || '' === trim($url) ? null : trim($url);

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
     * Whether a visitor can follow it: the page is published, or the address is an http(s)
     * one or a shop path (checked again here, the form is not the only writer).
     */
    public function isShown(): bool
    {
        return match (true) {
            null !== $this->page => $this->page->isPublished(),
            null !== $this->support => true,
            default => null !== $this->name && null !== $this->url && 1 === preg_match(Homepage::LINK_PATTERN, $this->url),
        };
    }

    public function __toString(): string
    {
        return (string) ($this->name ?? $this->page?->getTitle() ?? $this->support?->getName() ?? $this->url);
    }
}
