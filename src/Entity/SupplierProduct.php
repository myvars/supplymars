<?php

namespace App\Entity;

use App\Event\SupplierProductCostChangedEvent;
use App\Event\SupplierProductStockChangedEvent;
use App\Repository\SupplierProductRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SupplierProductRepository::class)]
class SupplierProduct implements DomainEventProviderInterface
{
    use TimestampableEntity;
    use DomainEventTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter a supplier product name')]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter a product code')]
    private ?string $productCode = null;

    #[ORM\ManyToOne(inversedBy: 'supplierProducts')]
    #[Assert\NotBlank(message: 'Please enter a supplier')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Supplier $supplier = null;

    #[ORM\ManyToOne(inversedBy: 'supplierProducts')]
    private ?SupplierCategory $supplierCategory = null;

    #[ORM\ManyToOne(inversedBy: 'supplierProducts')]
    private ?SupplierSubcategory $supplierSubcategory = null;

    #[ORM\ManyToOne(inversedBy: 'supplierProducts')]
    private ?SupplierManufacturer $supplierManufacturer = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter a manufacturer part number')]
    private ?string $mfrPartNumber = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Please enter a weight')]
    #[Assert\Range(notInRangeMessage: 'Please enter a product weight(grams)', min: 0, max: 100000)]
    private ?int $weight = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Please enter a stock level')]
    #[Assert\Range(notInRangeMessage: 'Please enter a stock level', min: 0, max: 10000)]
    private ?int $stock = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Please enter a lead time')]
    #[Assert\Range(notInRangeMessage: 'Please enter a lead time(days)', min: 0, max: 1000)]
    private ?int $leadTimeDays = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Please enter a cost')]
    #[Assert\PositiveOrZero(message: 'Please enter a positive or zero cost')]
    private ?string $cost = null;

    #[ORM\ManyToOne(inversedBy: 'supplierProducts')]
    private ?Product $product = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getProductCode(): ?string
    {
        return $this->productCode;
    }

    public function setProductCode(?string $productCode): static
    {
        $this->productCode = $productCode;

        return $this;
    }

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(?Supplier $supplier): static
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function getSupplierCategory(): ?SupplierCategory
    {
        return $this->supplierCategory;
    }

    public function setSupplierCategory(?SupplierCategory $supplierCategory): static
    {
        $this->supplierCategory = $supplierCategory;

        return $this;
    }

    public function getSupplierSubcategory(): ?SupplierSubcategory
    {
        return $this->supplierSubcategory;
    }

    public function setSupplierSubcategory(?SupplierSubcategory $supplierSubcategory): static
    {
        $this->supplierSubcategory = $supplierSubcategory;

        return $this;
    }

    public function getSupplierManufacturer(): ?SupplierManufacturer
    {
        return $this->supplierManufacturer;
    }

    public function setSupplierManufacturer(?SupplierManufacturer $supplierManufacturer): static
    {
        $this->supplierManufacturer = $supplierManufacturer;

        return $this;
    }

    public function getMfrPartNumber(): ?string
    {
        return $this->mfrPartNumber;
    }

    public function setMfrPartNumber(?string $mfrPartNumber): static
    {
        $this->mfrPartNumber = $mfrPartNumber;

        return $this;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function setWeight(?int $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(?int $stock): static
    {
        if ($stock === $this->getStock()) {
            return $this;
        }

        $this->stock = $stock;
        $this->raiseDomainEvent(new SupplierProductStockChangedEvent($this));

        return $this;
    }

    public function getLeadTimeDays(): ?int
    {
        return $this->leadTimeDays;
    }

    public function setLeadTimeDays(?int $leadTimeDays): static
    {
        $this->leadTimeDays = $leadTimeDays;

        return $this;
    }

    public function getCost(): ?string
    {
        return $this->cost;
    }

    public function setCost(?string $cost): static
    {
        if ($cost === $this->getCost()) {
            return $this;
        }

        $this->cost = $cost;
        $this->raiseDomainEvent(new SupplierProductCostChangedEvent($this));

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function isMapped(): bool
    {
        return $this->product instanceof Product;
    }

    public function hasActiveSupplier(): bool
    {
        return $this->supplier->isActive();
    }

    public function hasStock(): bool
    {
        return $this->stock > 0;
    }
}
