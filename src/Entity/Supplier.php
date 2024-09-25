<?php

namespace App\Entity;

use App\Repository\SupplierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SupplierRepository::class)]
class Supplier
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter a supplier name')]
    private ?string $name = null;

    #[ORM\Column(length: 20)]
    private string $colourScheme = 'supplier1';

    #[ORM\Column]
    private bool $isActive = false;

    #[ORM\OneToMany(targetEntity: SupplierCategory::class, mappedBy: 'supplier')]
    private Collection $supplierCategories;

    #[ORM\OneToMany(targetEntity: SupplierSubcategory::class, mappedBy: 'supplier')]
    private Collection $supplierSubcategories;

    #[ORM\OneToMany(targetEntity: SupplierManufacturer::class, mappedBy: 'supplier')]
    private Collection $supplierManufacturers;

    #[ORM\OneToMany(targetEntity: SupplierProduct::class, mappedBy: 'supplier')]
    private Collection $supplierProducts;

    /**
     * @var Collection<int, PurchaseOrder>
     */
    #[ORM\OneToMany(targetEntity: PurchaseOrder::class, mappedBy: 'supplier')]
    private Collection $purchaseOrders;

    #[ORM\Column]
    private bool $isWarehouse = false;

    public function __construct()
    {
        $this->supplierCategories = new ArrayCollection();
        $this->supplierSubcategories = new ArrayCollection();
        $this->supplierManufacturers = new ArrayCollection();
        $this->supplierProducts = new ArrayCollection();
        $this->purchaseOrders = new ArrayCollection();
    }

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

    public function getColourScheme(): string
    {
        return $this->colourScheme;
    }

    public function setColourScheme(string $colourScheme): Supplier
    {
        $this->colourScheme = $colourScheme;

        return $this;
    }

    public function isWarehouse(): bool
    {
        return $this->isWarehouse;
    }

    public function setIsWarehouse(bool $isWarehouse): static
    {
        $this->isWarehouse = $isWarehouse;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * @return Collection<int, SupplierCategory>
     */
    public function getSupplierCategories(): Collection
    {
        return $this->supplierCategories;
    }

    public function addSupplierCategory(SupplierCategory $supplierCategory): static
    {
        if (!$this->supplierCategories->contains($supplierCategory)) {
            $this->supplierCategories->add($supplierCategory);
            $supplierCategory->setSupplier($this);
        }

        return $this;
    }

    public function removeSupplierCategory(SupplierCategory $supplierCategory): static
    {
        // set the owning side to null (unless already changed)
        if ($this->supplierCategories->removeElement($supplierCategory) && $supplierCategory->getSupplier() === $this) {
            $supplierCategory->setSupplier(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, SupplierSubcategory>
     */
    public function getSupplierSubcategories(): Collection
    {
        return $this->supplierSubcategories;
    }

    public function addSupplierSubcategory(SupplierSubcategory $supplierSubcategory): static
    {
        if (!$this->supplierSubcategories->contains($supplierSubcategory)) {
            $this->supplierSubcategories->add($supplierSubcategory);
            $supplierSubcategory->setSupplier($this);
        }

        return $this;
    }

    public function removeSupplierSubcategory(SupplierSubcategory $supplierSubcategory): static
    {
        // set the owning side to null (unless already changed)
        if (
            $this->supplierSubcategories->removeElement($supplierSubcategory)
            && $supplierSubcategory->getSupplier() === $this
        ) {
            $supplierSubcategory->setSupplier(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, SupplierManufacturer>
     */
    public function getSupplierManufacturers(): Collection
    {
        return $this->supplierManufacturers;
    }

    public function addSupplierManufacturer(SupplierManufacturer $supplierManufacturer): static
    {
        if (!$this->supplierManufacturers->contains($supplierManufacturer)) {
            $this->supplierManufacturers->add($supplierManufacturer);
            $supplierManufacturer->setSupplier($this);
        }

        return $this;
    }

    public function removeSupplierManufacturer(SupplierManufacturer $supplierManufacturer): static
    {
        // set the owning side to null (unless already changed)
        if (
            $this->supplierManufacturers->removeElement($supplierManufacturer)
            && $supplierManufacturer->getSupplier() === $this
        ) {
            $supplierManufacturer->setSupplier(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, SupplierProduct>
     */
    public function getSupplierProducts(): Collection
    {
        return $this->supplierProducts;
    }

    public function addSupplierProduct(SupplierProduct $supplierProduct): static
    {
        if (!$this->supplierProducts->contains($supplierProduct)) {
            $this->supplierProducts->add($supplierProduct);
            $supplierProduct->setSupplier($this);
        }

        return $this;
    }

    public function removeSupplierProduct(SupplierProduct $supplierProduct): static
    {
        // set the owning side to null (unless already changed)
        if ($this->supplierProducts->removeElement($supplierProduct) && $supplierProduct->getSupplier() === $this) {
            $supplierProduct->setSupplier(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, PurchaseOrder>
     */
    public function getPurchaseOrders(): Collection
    {
        return $this->purchaseOrders;
    }

    public function addPurchaseOrder(PurchaseOrder $purchaseOrder): static
    {
        if (!$this->purchaseOrders->contains($purchaseOrder)) {
            $this->purchaseOrders->add($purchaseOrder);
            $purchaseOrder->setSupplier($this);
        }

        return $this;
    }

    public function removePurchaseOrder(PurchaseOrder $purchaseOrder): static
    {
        // set the owning side to null (unless already changed)
        if ($this->purchaseOrders->removeElement($purchaseOrder) && $purchaseOrder->getSupplier() === $this) {
            $purchaseOrder->setSupplier(null);
        }

        return $this;
    }
}
