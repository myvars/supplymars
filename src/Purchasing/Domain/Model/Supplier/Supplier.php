<?php

namespace App\Purchasing\Domain\Model\Supplier;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Purchasing\Domain\Model\Supplier\Event\SupplierStatusWasChangedEvent;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierCategory;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierManufacturer;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierSubcategory;
use App\Purchasing\Infrastructure\Persistence\Doctrine\SupplierDoctrineRepository;
use App\Shared\Domain\Event\DomainEventProviderInterface;
use App\Shared\Domain\Event\DomainEventProviderTrait;
use App\Shared\Infrastructure\Persistence\Doctrine\Mapping\HasPublicUlid;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SupplierDoctrineRepository::class)]
class Supplier implements DomainEventProviderInterface
{
    use TimestampableEntity;
    use DomainEventProviderTrait;
    use HasPublicUlid;

    public const string DEFAULT_WAREHOUSE_NAME = 'Turtle Inc';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter a supplier name')]
    private ?string $name = null;

    #[ORM\Column]
    private bool $isActive = false;

    /** @var Collection<int, SupplierCategory> */
    #[ORM\OneToMany(targetEntity: SupplierCategory::class, mappedBy: 'supplier')]
    private Collection $supplierCategories;

    /** @var Collection<int, SupplierSubcategory> */
    #[ORM\OneToMany(targetEntity: SupplierSubcategory::class, mappedBy: 'supplier')]
    private Collection $supplierSubcategories;

    /** @var Collection<int, SupplierManufacturer> */
    #[ORM\OneToMany(targetEntity: SupplierManufacturer::class, mappedBy: 'supplier')]
    private Collection $supplierManufacturers;

    /** @var Collection<int, SupplierProduct> */
    #[ORM\OneToMany(targetEntity: SupplierProduct::class, mappedBy: 'supplier')]
    private Collection $supplierProducts;

    /**
     * @var Collection<int, PurchaseOrder>
     */
    #[ORM\OneToMany(targetEntity: PurchaseOrder::class, mappedBy: 'supplier')]
    private Collection $purchaseOrders;

    #[ORM\Column]
    private bool $isWarehouse = false;

    final public function __construct()
    {
        $this->initializePublicId();
        $this->supplierCategories = new ArrayCollection();
        $this->supplierSubcategories = new ArrayCollection();
        $this->supplierManufacturers = new ArrayCollection();
        $this->supplierProducts = new ArrayCollection();
        $this->purchaseOrders = new ArrayCollection();
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

    public function setAsWarehouse(bool $isWarehouse): void
    {
        $this->isWarehouse = $isWarehouse;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublicId(): SupplierPublicId
    {
        return SupplierPublicId::fromString($this->publicIdString());
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getColourScheme(): string
    {
        // return a colour scheme based on the supplier ID
        return 'supplier' . ($this->getId() < 5 ? $this->getId() : 1);
    }

    public function isWarehouse(): bool
    {
        return $this->isWarehouse;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    private function rename(string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Supplier name cannot be empty');
        }

        $this->name = $name;
    }

    private function setActive(bool $active): void
    {
        if ($this->isActive === $active) {
            return;
        }

        $this->isActive = $active;

        $this->raiseDomainEvent(
            new SupplierStatusWasChangedEvent($this->getPublicId(), $this->isActive)
        );
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
            $supplierCategory->assignSupplier($this);
        }

        return $this;
    }

    public function removeSupplierCategory(SupplierCategory $supplierCategory): static
    {
        // set the owning side to null (unless already changed)
        if ($this->supplierCategories->removeElement($supplierCategory) && $supplierCategory->getSupplier() === $this) {
            $supplierCategory->assignSupplier(null);
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
            $supplierSubcategory->assignSupplier($this);
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
            $supplierSubcategory->assignSupplier(null);
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
            $supplierManufacturer->assignSupplier($this);
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
            $supplierManufacturer->assignSupplier(null);
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
            $supplierProduct->assignSupplier($this);
        }

        return $this;
    }

    public function removeSupplierProduct(SupplierProduct $supplierProduct): static
    {
        // set the owning side to null (unless already changed)
        if ($this->supplierProducts->removeElement($supplierProduct) && $supplierProduct->getSupplier() === $this) {
            $supplierProduct->assignSupplier(null);
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
            $purchaseOrder->assignSupplier($this);
        }

        return $this;
    }

    public function removePurchaseOrder(PurchaseOrder $purchaseOrder): static
    {
        // set the owning side to null (unless already changed)
        if ($this->purchaseOrders->removeElement($purchaseOrder) && $purchaseOrder->getSupplier() === $this) {
        }

        return $this;
    }
}
