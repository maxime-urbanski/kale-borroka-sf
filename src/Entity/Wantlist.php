<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\WantlistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WantlistRepository::class)]
class Wantlist
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToMany(targetEntity: Article::class)]
    private Collection $product;

    #[ORM\OneToOne(inversedBy: 'wantlist', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $userWantlist = null;

    public function __construct()
    {
        $this->product = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Article>
     */
    public function getProduct(): Collection
    {
        return $this->product;
    }

    public function addProduct(Article $product): static
    {
        if (!$this->product->contains($product)) {
            $this->product->add($product);
        }

        return $this;
    }

    public function removeProduct(Article $product): static
    {
        $this->product->removeElement($product);

        return $this;
    }

    public function getUserWantlist(): ?User
    {
        return $this->userWantlist;
    }

    public function setUserWantlist(User $userWantlist): static
    {
        $this->userWantlist = $userWantlist;

        return $this;
    }
}
