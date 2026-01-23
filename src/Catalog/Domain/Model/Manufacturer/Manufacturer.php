<?php

namespace App\Catalog\Domain\Model\Manufacturer;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Infrastructure\Persistence\Doctrine\ManufacturerDoctrineRepository;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierManufacturer;
use App\Shared\Infrastructure\Persistence\Doctrine\Mapping\HasPublicUlid;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ManufacturerDoctrineRepository::class)]
class Manufacturer
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

    #[ORM\Column]
    private bool $isActive = false;

    /** @var Collection<int, Product> */
    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'manufacturer')]
    private Collection $products;

    /** @var Collection<int, SupplierManufacturer> */
    #[ORM\OneToMany(targetEntity: SupplierManufacturer::class, mappedBy: 'mappedManufacturer')]
    private Collection $supplierManufacturers;

    final public function __construct()
    {
        $this->initializePublicId();
        $this->products = new ArrayCollection();
        $this->supplierManufacturers = new ArrayCollection();
    }

    public static function create(string $name, bool $isActive): self
    {
        $self = new self();
        $self->rename($name);
        $self->setActive($isActive);

        return $self;
    }

    public function update(string $name, bool $isActive): void
    {
        $this->rename($name);
        $this->setActive($isActive);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublicId(): ManufacturerPublicId
    {
        return ManufacturerPublicId::fromString($this->publicIdString());
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    private function rename(string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Manufacturer name cannot be empty');
        }

        $this->name = $name;
    }

    private function setActive(bool $active): void
    {
        if ($this->isActive === $active) {
            return;
        }

        $this->isActive = $active;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->assignManufacturer($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        // set the owning side to null (unless already changed)
        if ($this->products->removeElement($product) && $product->getManufacturer() === $this) {
            $product->assignManufacturer(null);
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
            $supplierManufacturer->assignMappedManufacturer($this);
        }

        return $this;
    }

    public function removeSupplierManufacturer(SupplierManufacturer $supplierManufacturer): static
    {
        // set the owning side to null (unless already changed)
        if ($this->supplierManufacturers->removeElement($supplierManufacturer)
            && $supplierManufacturer->getMappedManufacturer() === $this
        ) {
            $supplierManufacturer->assignMappedManufacturer(null);
        }

        return $this;
    }
}
