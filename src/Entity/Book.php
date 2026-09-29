<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\BookType;
use App\Enum\SupportType;
use App\Repository\BookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A fanzine or a book (schema.org: Book + Product). Listed under the fanzine section.
 */
#[ORM\Entity(repositoryClass: BookRepository::class)]
class Book extends Article
{
    #[ORM\Column(length: 20, nullable: true, enumType: BookType::class)]
    #[Assert\NotNull(message: 'Fanzine ou livre ?')]
    private ?BookType $bookType = BookType::FANZINE;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $author = null;

    #[ORM\Column(length: 17, nullable: true)]
    #[Assert\Isbn(message: 'Cet ISBN n\'est pas valide.')]
    private ?string $isbn = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive]
    private ?int $numberOfPages = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'publisher_id')]
    private ?Label $publisher = null;

    #[ORM\ManyToOne]
    private ?Category $category = null;

    public function getSupportType(): SupportType
    {
        return SupportType::FANZINE;
    }

    public function getFormatLabel(): string
    {
        return $this->bookType?->label() ?? '';
    }

    protected function getParentImages(): Collection
    {
        return new ArrayCollection();
    }

    public function getBookType(): ?BookType
    {
        return $this->bookType;
    }

    public function setBookType(?BookType $bookType): static
    {
        $this->bookType = $bookType;

        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(?string $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    public function setIsbn(?string $isbn): static
    {
        $this->isbn = $isbn;

        return $this;
    }

    public function getNumberOfPages(): ?int
    {
        return $this->numberOfPages;
    }

    public function setNumberOfPages(?int $numberOfPages): static
    {
        $this->numberOfPages = $numberOfPages;

        return $this;
    }

    public function getPublisher(): ?Label
    {
        return $this->publisher;
    }

    public function setPublisher(?Label $publisher): static
    {
        $this->publisher = $publisher;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }
}
