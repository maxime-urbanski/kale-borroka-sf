<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AddressRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * An entry of a customer's address book: where to deliver, and whom. Orders copy it as text
 * (__toString()) when they are placed.
 */
#[ORM\Entity(repositoryClass: AddressRepository::class)]
class Address
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Donne un nom à cette adresse.')]
    #[Assert\Length(max: 100)]
    private ?string $name = null;

    /** The recipient, as the carrier prints it on the parcel. */
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Indique le prénom de la personne qui reçoit le colis.')]
    #[Assert\Length(max: 100)]
    private ?string $firstname = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Indique le nom de la personne qui reçoit le colis.')]
    #[Assert\Length(max: 100)]
    private ?string $lastname = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Indique la rue et le numéro.')]
    #[Assert\Length(max: 255)]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $complement_address = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Indique la ville.')]
    #[Assert\Length(max: 100)]
    private ?string $city = null;

    #[ORM\Column(length: 15)]
    #[Assert\NotBlank(message: 'Indique le code postal.')]
    #[Assert\Length(max: 15)]
    private ?string $zipcode = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Indique le pays.')]
    #[Assert\Length(max: 100)]
    private ?string $country = null;

    /** Optional, for the carrier only. */
    #[ORM\Column(length: 30, nullable: true)]
    #[Assert\Length(max: 30)]
    #[Assert\Regex(pattern: '/^\+?[0-9 .()-]{6,}$/', message: 'Un numéro de téléphone : des chiffres, des espaces, un + devant.')]
    private ?string $telephone = null;

    #[ORM\ManyToOne(inversedBy: 'addresses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $users = null;

    #[ORM\Column]
    private ?bool $isMainAddress = null;

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

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getComplementAddress(): ?string
    {
        return $this->complement_address;
    }

    public function setComplementAddress(string $complement_address): static
    {
        $this->complement_address = $complement_address;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getZipcode(): ?string
    {
        return $this->zipcode;
    }

    public function setZipcode(string $zipcode): static
    {
        $this->zipcode = $zipcode;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getUsers(): ?User
    {
        return $this->users;
    }

    public function setUsers(?User $users): static
    {
        $this->users = $users;

        return $this;
    }

    public function isIsMainAddress(): ?bool
    {
        return $this->isMainAddress;
    }

    public function setIsMainAddress(bool $isMainAddress): static
    {
        $this->isMainAddress = $isMainAddress;

        return $this;
    }

    /**
     * Plain text, one line per part: escape it, then |nl2br, to show it as HTML.
     */
    public function __toString(): string
    {
        return implode("\n", array_filter([
            $this->name,
            trim($this->firstname.' '.$this->lastname),
            $this->address,
            $this->complement_address,
            trim($this->zipcode.' '.$this->city),
            $this->country,
            $this->telephone,
        ], static fn (?string $line): bool => null !== $line && '' !== $line));
    }
}
