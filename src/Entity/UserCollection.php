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

    #[ORM\OneToOne(inversedBy: 'user_collection', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user_collection = null;

    #[ORM\ManyToMany(targetEntity: Article::class, inversedBy: 'userCollections')]
    #[ORM\JoinTable(name: 'user_collection_items')]
    private Collection $article;

    public function __construct()
    {
        $this->article = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserCollection(): ?User
    {
        return $this->user_collection;
    }

    public function setUserCollection(User $user_collection): static
    {
        $this->user_collection = $user_collection;

        return $this;
    }

    /**
     * @return Collection<int, Article>
     */
    public function getArticle(): Collection
    {
        return $this->article;
    }

    public function addArticle(Article $article): static
    {
        if (!$this->article->contains($article)) {
            $this->article->add($article);
        }

        return $this;
    }

    public function removeArticle(Article $article): static
    {
        $this->article->removeElement($article);

        return $this;
    }
}
