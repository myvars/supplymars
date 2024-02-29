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
    #[Assert\NotNull(message: 'Please enter a supplier name')]
    private ?string $name = null;

    #[ORM\Column]
    private bool $isActive = false;

    #[ORM\OneToMany(mappedBy: 'supplier', targetEntity: SupplierCategory::class)]
    private Collection $supplierCategories;

    #[ORM\OneToMany(mappedBy: 'supplier', targetEntity: SupplierSubcategory::class)]
    private Collection $supplierSubcategories;

    #[ORM\OneToMany(mappedBy: 'supplier', targetEntity: SupplierManufacturer::class)]
    private Collection $supplierManufacturers;

    #[ORM\OneToMany(mappedBy: 'supplier', targetEntity: SupplierProduct::class)]
    private Collection $supplierProducts;

    public function __construct()
    {
        $this->supplierCategories = new ArrayCollection();
        $this->supplierSubcategories = new ArrayCollection();
        $this->supplierManufacturers = new ArrayCollection();
        $this->supplierProducts = new ArrayCollection();
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

    public function isIsActive(): ?bool
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
        if ($this->supplierCategories->removeElement($supplierCategory)) {
            // set the owning side to null (unless already changed)
            if ($supplierCategory->getSupplier() === $this) {
                $supplierCategory->setSupplier(null);
            }
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
        if ($this->supplierSubcategories->removeElement($supplierSubcategory)) {
            // set the owning side to null (unless already changed)
            if ($supplierSubcategory->getSupplier() === $this) {
                $supplierSubcategory->setSupplier(null);
            }
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
        if ($this->supplierManufacturers->removeElement($supplierManufacturer)) {
            // set the owning side to null (unless already changed)
            if ($supplierManufacturer->getSupplier() === $this) {
                $supplierManufacturer->setSupplier(null);
            }
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
        if ($this->supplierProducts->removeElement($supplierProduct)) {
            // set the owning side to null (unless already changed)
            if ($supplierProduct->getSupplier() === $this) {
                $supplierProduct->setSupplier(null);
            }
        }

        return $this;
    }
}
