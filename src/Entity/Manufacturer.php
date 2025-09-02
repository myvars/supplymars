<?php

namespace App\Entity;

use App\Repository\ManufacturerRepository;
use App\ValueObject\ManufacturerPublicId;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ManufacturerRepository::class)]
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

    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'manufacturer')]
    private Collection $products;

    #[ORM\OneToMany(targetEntity: SupplierManufacturer::class, mappedBy: 'mappedManufacturer')]
    private Collection $supplierManufacturers;

    public function __construct()
    {
        $this->initializePublicId();
        $this->products = new ArrayCollection();
        $this->supplierManufacturers = new ArrayCollection();
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

    public function setName(?string $name): static
    {
        $this->name = $name;

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
            $product->setManufacturer($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        // set the owning side to null (unless already changed)
        if ($this->products->removeElement($product) && $product->getManufacturer() === $this) {
            $product->setManufacturer(null);
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
            $supplierManufacturer->setMappedManufacturer($this);
        }

        return $this;
    }

    public function removeSupplierManufacturer(SupplierManufacturer $supplierManufacturer): static
    {
        // set the owning side to null (unless already changed)
        if (
            $this->supplierManufacturers->removeElement($supplierManufacturer)
            && $supplierManufacturer->getMappedManufacturer() === $this
        ) {
            $supplierManufacturer->setMappedManufacturer(null);
        }

        return $this;
    }
}
