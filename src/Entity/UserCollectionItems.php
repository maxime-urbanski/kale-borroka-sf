<?php

namespace App\Entity;

use App\Repository\UserCollectionItemsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserCollectionItemsRepository::class)]
class UserCollectionItems
{
    #[ORM\Column]
    private ?\DateTimeImmutable $added_at = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Article $article = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'collectionItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?UserCollection $collection = null;

    public function getAddedAt(): ?\DateTimeImmutable
    {
        return $this->added_at;
    }

    public function setAddedAt(\DateTimeImmutable $added_at): static
    {
        $this->added_at = $added_at;

        return $this;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): static
    {
        $this->article = $article;

        return $this;
    }

    public function getCollection(): ?UserCollection
    {
        return $this->collection;
    }

    public function setCollection(?UserCollection $collection): static
    {
        $this->collection = $collection;

        return $this;
    }
}
