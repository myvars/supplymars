<?php

namespace App\Entity;

use App\Repository\SupplierSubcategoryRepository;
use App\ValueObject\SupplierSubcategoryPublicId;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SupplierSubcategoryRepository::class)]
class SupplierSubcategory
{
    use TimestampableEntity;
    use HasPublicUlid;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter a Subcategory name')]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'supplierSubcategories')]
    #[Assert\NotNull(message: 'Please enter a supplier')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Supplier $supplier = null;

    #[ORM\ManyToOne(inversedBy: 'supplierSubcategories')]
    #[Assert\NotNull(message: 'Please enter a supplier category')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SupplierCategory $supplierCategory = null;

    #[ORM\ManyToOne(inversedBy: 'supplierSubcategories')]
    private ?Subcategory $mappedSubcategory = null;

    #[ORM\OneToMany(targetEntity: SupplierProduct::class, mappedBy: 'supplierSubcategory')]
    private Collection $supplierProducts;

    public function __construct()
    {
        $this->initializePublicId();
        $this->supplierProducts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublicId(): SupplierSubcategoryPublicId
    {
        return SupplierSubcategoryPublicId::fromString($this->publicIdString());
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

    public function getSupplierCategory(): ?SupplierCategory
    {
        return $this->supplierCategory;
    }

    public function setSupplierCategory(?SupplierCategory $supplierCategory): static
    {
        $this->supplierCategory = $supplierCategory;

        return $this;
    }

    public function getMappedSubcategory(): ?Subcategory
    {
        return $this->mappedSubcategory;
    }

    public function setMappedSubcategory(?Subcategory $mappedSubcategory): static
    {
        $this->mappedSubcategory = $mappedSubcategory;

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
            $supplierProduct->setSupplierSubcategory($this);
        }

        return $this;
    }

    public function removeSupplierProduct(SupplierProduct $supplierProduct): static
    {
        // set the owning side to null (unless already changed)
        if (
            $this->supplierProducts->removeElement($supplierProduct)
            && $supplierProduct->getSupplierSubcategory() === $this
        ) {
            $supplierProduct->setSupplierSubcategory(null);
        }

        return $this;
    }
}
