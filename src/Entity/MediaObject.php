<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MediaObjectRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * A file of the media library ("Médiathèque"), e.g. the social network icons.
 *
 * `filename` is the bare stored name, like every other upload: get the public URL with
 * vich_uploader_asset(media) in Twig, or /media/<filename>.
 */
#[ORM\Entity(repositoryClass: MediaObjectRepository::class)]
#[Vich\Uploadable]
class MediaObject
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[Vich\UploadableField(mapping: 'media_object', fileNameProperty: 'filename')]
    // No SVG: it can carry scripts, and /media is served from the shop's own origin.
    #[Assert\File(maxSize: '8M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], mimeTypesMessage: 'Image uniquement (JPEG, PNG, WebP, GIF).')]
    private ?File $file = null;

    #[ORM\Column(length: 255)]
    private ?string $filename = null;

    /** Vich only notices a replaced file if a mapped column changes. */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[Assert\Callback]
    public function validateHasFile(ExecutionContextInterface $context): void
    {
        if (null === $this->filename && null === $this->file) {
            $context->buildViolation('Choisissez un fichier.')->atPath('file')->addViolation();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file): static
    {
        $this->file = $file;

        if (null !== $file) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function __toString(): string
    {
        return (string) $this->filename;
    }
}
