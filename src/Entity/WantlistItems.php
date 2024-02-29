<?php

namespace App\Entity;

use App\Repository\WantlistItemsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WantlistItemsRepository::class)]
class WantlistItems
{
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $since = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Wantlist::class)]
    private ?Wantlist $wantlist = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Article::class)]
    private ?Article $article = null;

    public function getSince(): ?\DateTimeInterface
    {
        return $this->since;
    }

    public function setSince(\DateTimeInterface $since): static
    {
        $this->since = $since;

        return $this;
    }

    public function getWantlist(): ?Wantlist
    {
        return $this->wantlist;
    }

    public function setWantlist(?Wantlist $wantlist): static
    {
        $this->wantlist = $wantlist;

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
}
