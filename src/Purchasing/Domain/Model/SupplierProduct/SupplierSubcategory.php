<?php

namespace App\Purchasing\Domain\Model\SupplierProduct;

use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Infrastructure\Persistence\Doctrine\SupplierSubcategoryDoctrineRepository;
use App\Shared\Infrastructure\Persistence\Doctrine\Mapping\HasPublicUlid;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SupplierSubcategoryDoctrineRepository::class)]
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

    public static function create(
        string $name,
        Supplier $supplier,
        SupplierCategory $supplierCategory,
    ): self {
        $self = new self();
        $self->rename($name);
        $self->assignSupplier($supplier);
        $self->assignSupplierCategory($supplierCategory);

        return $self;
    }

    public function update(
        string $name,
        Supplier $supplier,
        SupplierCategory $supplierCategory,
    ): void {
        $this->rename($name);
        $this->assignSupplier($supplier);
        $this->assignSupplierCategory($supplierCategory);
    }

    public function assignSupplier(?Supplier $supplier): void
    {
        $this->supplier = $supplier;
    }

    public function assignSupplierCategory(?SupplierCategory $supplierCategory): void
    {
        $this->supplierCategory = $supplierCategory;
    }

    public function assignMappedSubcategory(?Subcategory $mappedSubcategory): void
    {
        $this->mappedSubcategory = $mappedSubcategory;
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

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function getSupplierCategory(): ?SupplierCategory
    {
        return $this->supplierCategory;
    }

    public function getMappedSubcategory(): ?Subcategory
    {
        return $this->mappedSubcategory;
    }

    private function rename(string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Subcategory name cannot be empty');
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
            $supplierProduct->assignSupplierSubcategory($this);
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
            $supplierProduct->assignSupplierSubcategory(null);
        }

        return $this;
    }
}
