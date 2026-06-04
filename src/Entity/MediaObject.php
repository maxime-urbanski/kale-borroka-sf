<?php

namespace App\Entity;

use App\Repository\MediaObjectRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: MediaObjectRepository::class)]
#[Vich\Uploadable]
class MediaObject
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[Vich\UploadableField(mapping: 'media_object', fileNameProperty: 'filename')]
    public ?File $file = null;

    #[ORM\Column(length: 255)]
    public ?string $filename = null;

    public function __toString(): string
    {
        return $this->filename;
    }
}
