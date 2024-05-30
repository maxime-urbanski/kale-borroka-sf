<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SupportRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SupportRepository::class)]
class Support
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    #[Groups('support:slider')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups('support:slider')]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $icon = null;

    #[ORM\OneToMany(mappedBy: 'support', targetEntity: Article::class)]
    private Collection $articles;

    public function __construct()
    {
        $this->articles = new ArrayCollection();
    }

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

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @return Collection<int, Article>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(Article $article): static
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
            $article->setSupport($this);
        }

        return $this;
    }

    public function removeArticle(Article $article): static
    {
        // set the owning side to null (unless already changed)
        if ($this->articles->removeElement($article) && $article->getSupport() === $this) {
            $article->setSupport(null);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
