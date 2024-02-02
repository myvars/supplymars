<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter a product name')]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter a Mfr part number')]
    private ?string $MfrPartNumber = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Range(notInRangeMessage: 'Please enter a stock level', min: 0, max: 10000)]
    private ?int $stock = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Range(notInRangeMessage: 'Please enter a lead time(days)', min: 0, max: 1000)]
    private ?int $leadTimeDays = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Range(notInRangeMessage: 'Please enter a product weight(grams)', min: 0, max: 100000)]
    private ?int $weight = null;

    #[ORM\Column(nullable: true)]
    #[Assert\NotNull]
    #[Assert\Range(notInRangeMessage: 'Please enter a valid markup', min: 0, max: 100000)]
    private ?int $markup = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Range(notInRangeMessage: 'Please enter a valid cost', min: 0, max: 1000000)]
    private ?int $cost = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Range(notInRangeMessage: 'Please enter a valid sell price', min: 0, max: 1000000)]
    private ?int $sellPrice = null;

    #[ORM\ManyToOne]
    #[Assert\Range(notInRangeMessage: 'Please enter a valid VAT rate', min: 0, max: 10000)]
    private ?VatRate $vatRate = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Please enter a Category')]
    private ?Category $category = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Please enter a Subcategory')]
    private ?Subcategory $subcategory = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Please enter a Manufacturer')]
    private ?Manufacturer $manufacturer = null;

    #[ORM\ManyToOne]
    private ?User $owner = null;

    #[ORM\Column]
    private bool $isActive = false;

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

    public function getMfrPartNumber(): ?string
    {
        return $this->MfrPartNumber;
    }

    public function setMfrPartNumber(string $MfrPartNumber): static
    {
        $this->MfrPartNumber = $MfrPartNumber;

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function getLeadTimeDays(): ?int
    {
        return $this->leadTimeDays;
    }

    public function setLeadTimeDays(int $leadTimeDays): static
    {
        $this->leadTimeDays = $leadTimeDays;

        return $this;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function getMarkup(): ?int
    {
        return $this->markup;
    }

    public function setMarkup(?int $markup): static
    {
        $this->markup = $markup;

        return $this;
    }

    public function getCost(): ?int
    {
        return $this->cost;
    }

    public function setCost(int $cost): static
    {
        $this->cost = $cost;

        return $this;
    }

    public function getSellPrice(): ?int
    {
        return $this->sellPrice;
    }

    public function setSellPrice(int $sellPrice): static
    {
        $this->sellPrice = $sellPrice;

        return $this;
    }

    public function getVatRate(): ?VatRate
    {
        return $this->vatRate;
    }

    public function setVatRate(?VatRate $vatRate): static
    {
        $this->vatRate = $vatRate;

        return $this;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getSubcategory(): Subcategory
    {
        return $this->subcategory;
    }

    public function setSubcategory(Subcategory $subcategory): static
    {
        $this->subcategory = $subcategory;

        return $this;
    }

    public function getManufacturer(): Manufacturer
    {
        return $this->manufacturer;
    }

    public function setManufacturer(Manufacturer $manufacturer): static
    {
        $this->manufacturer = $manufacturer;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function isIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }
}
