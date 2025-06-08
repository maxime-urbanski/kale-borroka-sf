<?php

namespace App\Entity;

use App\Repository\UserCollectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserCollectionRepository::class)]
class UserCollection
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\OneToMany(mappedBy: 'collection', targetEntity: UserCollectionItems::class)]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, UserCollectionItems>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItems(UserCollectionItems $items): static
    {
        if (!$this->items->contains($items)) {
            $this->items->add($items);
            $items->setCollection($this);
        }

        return $this;
    }

    public function removeItems(UserCollectionItems $items): static
    {
        if ($this->items->removeElement($items)) {
            // set the owning side to null (unless already changed)
            if ($items->getCollection() === $this) {
                $items->setCollection(null);
            }
        }

        return $this;
    }
}
