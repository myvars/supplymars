<?php

namespace App\Entity;

use App\Repository\SupplierManufacturerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SupplierManufacturerRepository::class)]
class SupplierManufacturer
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotNull(message: 'Please enter a manufacturer name')]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'supplierManufacturers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Supplier $supplier = null;

    #[ORM\ManyToOne(inversedBy: 'supplierManufacturers')]
    private ?Manufacturer $mappedManufacturer = null;

    #[ORM\OneToMany(targetEntity: SupplierProduct::class, mappedBy: 'supplierManufacturer')]
    private Collection $supplierProducts;

    public function __construct()
    {
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

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(?Supplier $supplier): static
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function getMappedManufacturer(): ?Manufacturer
    {
        return $this->mappedManufacturer;
    }

    public function setMappedManufacturer(?Manufacturer $mappedManufacturer): static
    {
        $this->mappedManufacturer = $mappedManufacturer;

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
            $supplierProduct->setSupplierManufacturer($this);
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
            $supplierProduct->setSupplierManufacturer(null);
        }

        return $this;
    }
}
