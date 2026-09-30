<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ImageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * An uploaded picture: album covers, and extra pictures of articles and merch.
 */
#[ORM\Entity(repositoryClass: ImageRepository::class)]
#[Vich\Uploadable]
class Image
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageName = null;

    #[ORM\Column(nullable: true)]
    private ?int $imageSize = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /** Order among an album's pictures; the first one is the cover. */
    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    #[Vich\UploadableField(mapping: 'albums', fileNameProperty: 'imageName', size: 'imageSize')]
    #[Assert\Image(maxSize: '8M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp'], mimeTypesMessage: 'JPEG, PNG ou WebP uniquement.')]
    private ?File $imageFile = null;

    /** @var Collection<int, Album> */
    #[ORM\ManyToMany(targetEntity: Album::class, inversedBy: 'images')]
    private Collection $album;

    public function __construct()
    {
        $this->album = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * A picture without a file would render as a broken image everywhere.
     */
    #[Assert\Callback]
    public function validateHasFile(ExecutionContextInterface $context): void
    {
        if (null === $this->imageName && null === $this->imageFile) {
            $context->buildViolation('Choisissez un fichier.')->atPath('imageFile')->addViolation();
        }
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = (int) $position;

        return $this;
    }

    /**
     * The picture with the lowest position. Sorted here rather than trusting the
     * collection order: #[ORM\OrderBy] only applies when a collection is loaded.
     *
     * @param iterable<Image> $images
     */
    public static function first(iterable $images): ?self
    {
        $first = null;

        foreach ($images as $image) {
            if (null === $first || $image->getPosition() < $first->getPosition()) {
                $first = $image;
            }
        }

        return $first;
    }

    public function __toString(): string
    {
        return (string) $this->imageName;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageName(?string $imageName): static
    {
        $this->imageName = $imageName;

        return $this;
    }

    public function getImageSize(): ?int
    {
        return $this->imageSize;
    }

    public function setImageSize(?int $imageSize): static
    {
        $this->imageSize = $imageSize;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     */
    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    /**
     * @return Collection<int, Album>
     */
    public function getAlbum(): Collection
    {
        return $this->album;
    }

    public function addAlbum(Album $album): static
    {
        if (!$this->album->contains($album)) {
            $this->album->add($album);
        }

        return $this;
    }

    public function removeAlbum(Album $album): static
    {
        $this->album->removeElement($album);

        return $this;
    }
}
