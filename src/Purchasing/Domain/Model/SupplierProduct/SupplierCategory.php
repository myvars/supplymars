<?php

namespace App\Purchasing\Domain\Model\SupplierProduct;

use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Infrastructure\Persistence\Doctrine\SupplierCategoryDoctrineRepository;
use App\Shared\Infrastructure\Persistence\Doctrine\Mapping\HasPublicUlid;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SupplierCategoryDoctrineRepository::class)]
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

    public static function create(string $name, Supplier $supplier): self
    {
        $self = new self();
        $self->rename($name);
        $self->assignSupplier($supplier);

        return $self;
    }

    public function update(string $name, Supplier $supplier): void
    {
        $this->rename($name);
        $this->assignSupplier($supplier);
    }

    public function assignSupplier(?Supplier $supplier): void
    {
        $this->supplier = $supplier;
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

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    private function rename(string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Category name cannot be empty');
        }
        $this->name = $name;
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
            $supplierProduct->assignSupplierCategory($this);
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
            $supplierProduct->assignSupplierCategory(null);
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
            $supplierSubcategory->assignSupplierCategory($this);
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
            $supplierSubcategory->assignSupplierCategory(null);
        }

        return $this;
    }
}
