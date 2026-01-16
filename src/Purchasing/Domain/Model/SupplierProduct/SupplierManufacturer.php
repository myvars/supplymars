<?php

namespace App\Purchasing\Domain\Model\SupplierProduct;

use App\Catalog\Domain\Model\Manufacturer\Manufacturer;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Infrastructure\Persistence\Doctrine\SupplierManufacturerDoctrineRepository;
use App\Shared\Infrastructure\Persistence\Doctrine\Mapping\HasPublicUlid;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SupplierManufacturerDoctrineRepository::class)]
class SupplierManufacturer
{
    use TimestampableEntity;
    use HasPublicUlid;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter a manufacturer name')]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'supplierManufacturers')]
    #[Assert\NotNull(message: 'Please enter a supplier')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Supplier $supplier = null;

    #[ORM\ManyToOne(inversedBy: 'supplierManufacturers')]
    private ?Manufacturer $mappedManufacturer = null;

    #[ORM\OneToMany(targetEntity: SupplierProduct::class, mappedBy: 'supplierManufacturer')]
    private Collection $supplierProducts;

    public function __construct()
    {
        $this->initializePublicId();
        $this->supplierProducts = new ArrayCollection();
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

    public function assignMappedManufacturer(?Manufacturer $mappedManufacturer): static
    {
        $this->mappedManufacturer = $mappedManufacturer;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublicId(): SupplierManufacturerPublicId
    {
        return SupplierManufacturerPublicId::fromString($this->publicIdString());
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function getMappedManufacturer(): ?Manufacturer
    {
        return $this->mappedManufacturer;
    }

    private function rename(string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Manufacturer name cannot be empty');
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
            $supplierProduct->assignSupplierManufacturer($this);
        }

        return $this;
    }

    public function removeSupplierProduct(SupplierProduct $supplierProduct): static
    {
        // set the owning side to null (unless already changed)
        if (
            $this->supplierProducts->removeElement($supplierProduct)
            && $supplierProduct->getSupplierManufacturer() === $this
        ) {
            $supplierProduct->assignSupplierManufacturer(null);
        }

        return $this;
    }
}
