<?php

namespace App\Entity;

use App\Repository\AddressRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AddressRepository::class)]
class Address
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $complement_address = null;

    #[ORM\Column(length: 100)]
    private ?string $city = null;

    #[ORM\Column(length: 15)]
    private ?string $zipcode = null;

    #[ORM\Column(length: 100)]
    private ?string $country = null;

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

    public function __toString(): string
    {
        return $this->name;
    }
}
