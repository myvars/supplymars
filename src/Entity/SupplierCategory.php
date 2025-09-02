<?php

namespace App\Entity;

use App\Repository\SupplierCategoryRepository;
use App\ValueObject\SupplierCategoryPublicId;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SupplierCategoryRepository::class)]
class SupplierCategory
{
    use TimestampableEntity;
    use HasPublicUlid;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter a category name')]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'supplierCategories')]
    #[Assert\NotNull(message: 'Please enter a supplier')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Supplier $supplier = null;

    #[ORM\OneToMany(targetEntity: SupplierProduct::class, mappedBy: 'supplierCategory')]
    private Collection $supplierProducts;

    #[ORM\OneToMany(targetEntity: SupplierSubcategory::class, mappedBy: 'supplierCategory')]
    private Collection $supplierSubcategories;

    public function __construct()
    {
        $this->initializePublicId();
        $this->supplierProducts = new ArrayCollection();
        $this->supplierSubcategories = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublicId(): SupplierCategoryPublicId
    {
        return SupplierCategoryPublicId::fromString($this->publicIdString());
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

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(?Supplier $supplier): static
    {
        $this->supplier = $supplier;

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
            $supplierProduct->setSupplierCategory($this);
        }

        return $this;
    }

    public function removeSupplierProduct(SupplierProduct $supplierProduct): static
    {
        // set the owning side to null (unless already changed)
        if (
            $this->supplierProducts->removeElement($supplierProduct)
            && $supplierProduct->getSupplierCategory() === $this
        ) {
            $supplierProduct->setSupplierCategory(null);
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
            $supplierSubcategory->setSupplierCategory($this);
        }

        return $this;
    }

    public function removeSupplierSubcategory(SupplierSubcategory $supplierSubcategory): static
    {
        // set the owning side to null (unless already changed)
        if (
            $this->supplierSubcategories->removeElement($supplierSubcategory)
            && $supplierSubcategory->getSupplierCategory() === $this
        ) {
            $supplierSubcategory->setSupplierCategory(null);
        }

        return $this;
    }
}
