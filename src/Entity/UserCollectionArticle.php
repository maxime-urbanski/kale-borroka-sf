<?php

namespace App\Entity;

use App\Repository\UserCollectionArticleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserCollectionArticleRepository::class)]
class UserCollectionArticle
{
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $since = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: UserCollection::class)]
    #[ORM\JoinColumn(name: 'user_collection_id', referencedColumnName: 'id')]
    private ?UserCollection $user_collection = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Article::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'article_id', referencedColumnName: 'id')]
    private ?Article $user_article = null;

    public function getSince(): ?\DateTimeInterface
    {
        return $this->since;
    }

    public function setSince(?\DateTimeInterface $since): static
    {
        $this->since = $since;

        return $this;
    }

    public function getUserCollection(): ?UserCollection
    {
        return $this->user_collection;
    }

    public function setUserCollection(?UserCollection $user_collection): static
    {
        $this->user_collection = $user_collection;

        return $this;
    }

    public function getUserArticle(): ?Article
    {
        return $this->user_article;
    }

    public function setUserArticle(?Article $user_article): static
    {
        $this->user_article = $user_article;

        return $this;
    }
}
