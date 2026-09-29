<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\SupportType;
use App\Repository\SupportRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * A catalogue section as shown in the menus. `code` ties it to SupportType; `name` is the URL segment.
 */
#[ORM\Entity(repositoryClass: SupportRepository::class)]
class Support
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 20, unique: true, enumType: SupportType::class)]
    private ?SupportType $code = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $icon = null;

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

    public function __toString(): string
    {
        return (string) $this->name;
    }
}
