<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\FooterPlacement;
use App\Repository\PageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Editorial page managed from the back office (CGV, "Qui sommes-nous"…), shown at
 * /page/{slug} and, optionally, linked from the footer.
 */
#[ORM\Entity(repositoryClass: PageRepository::class)]
#[UniqueEntity(fields: ['slug'], message: 'Une autre page utilise déjà cette adresse.')]
class Page
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $title = null;

    /**
     * Generated from the title when left blank, then kept when the title changes so that
     * links do not break. Can be edited by hand.
     */
    #[ORM\Column(length: 255, unique: true)]
    #[Gedmo\Slug(fields: ['title'], updatable: false)]
    #[Assert\Regex(pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', message: 'Minuscules, chiffres et tirets uniquement (ex. : conditions-de-vente).')]
    private ?string $slug = null;

    /** HTML from the back office rich-text editor. */
    #[ORM\Column(type: Types::TEXT)]
    private string $content = '';

    #[ORM\Column(options: ['default' => false])]
    private bool $published = false;

    #[ORM\Column(length: 20, enumType: FooterPlacement::class, options: ['default' => 'none'])]
    private FooterPlacement $footerPlacement = FooterPlacement::NONE;

    /** Order within its footer column, smallest first. */
    #[ORM\Column(options: ['default' => 0])]
    private int $footerPosition = 0;

    #[ORM\Column]
    #[Gedmo\Timestampable]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
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

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = (string) $content;

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

    public function getFooterPlacement(): FooterPlacement
    {
        return $this->footerPlacement;
    }

    public function setFooterPlacement(FooterPlacement $footerPlacement): static
    {
        $this->footerPlacement = $footerPlacement;

        return $this;
    }

    public function getFooterPosition(): int
    {
        return $this->footerPosition;
    }

    public function setFooterPosition(?int $footerPosition): static
    {
        $this->footerPosition = (int) $footerPosition;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->title;
    }
}
