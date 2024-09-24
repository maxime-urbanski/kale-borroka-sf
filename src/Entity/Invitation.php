<?php

namespace App\Entity;

use App\Enum\StatusInvitationEnum;
use App\Repository\InvitationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvitationRepository::class)]
class Invitation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'invitations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $guest = null;

    #[ORM\ManyToOne(inversedBy: 'invitations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $sentBy = null;

    #[ORM\ManyToOne(inversedBy: 'invitations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Group $community = null;

    #[ORM\Column(length: 255)]
    private ?string $status = StatusInvitationEnum::Pending->value;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable('now');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGuest(): ?User
    {
        return $this->guest;
    }

    public function setGuest(?User $guest): static
    {
        $this->guest = $guest;

        return $this;
    }

    public function getSentBy(): ?User
    {
        return $this->sentBy;
    }

    public function setSentBy(?User $sentBy): static
    {
        $this->sentBy = $sentBy;

        return $this;
    }

    public function getCommunity(): ?Group
    {
        return $this->community;
    }

    public function setCommunity(?Group $community): static
    {
        $this->community = $community;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
