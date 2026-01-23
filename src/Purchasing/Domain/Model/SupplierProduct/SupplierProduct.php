<?php

namespace App\Purchasing\Domain\Model\SupplierProduct;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\Product\ProductId;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Model\SupplierProduct\Event\SupplierProductPricingWasChangedEvent;
use App\Purchasing\Domain\Model\SupplierProduct\Event\SupplierProductStatusWasChangedEvent;
use App\Purchasing\Domain\Model\SupplierProduct\Event\SupplierProductStockWasChangedEvent;
use App\Purchasing\Infrastructure\Persistence\Doctrine\SupplierProductDoctrineRepository;
use App\Shared\Domain\Event\DomainEventProviderInterface;
use App\Shared\Domain\Event\DomainEventProviderTrait;
use App\Shared\Domain\ValueObject\CostChange;
use App\Shared\Domain\ValueObject\StockChange;
use App\Shared\Infrastructure\Persistence\Doctrine\Mapping\HasPublicUlid;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SupplierProductDoctrineRepository::class)]
class SupplierProduct implements DomainEventProviderInterface
{
    use TimestampableEntity;
    use DomainEventProviderTrait;
    use HasPublicUlid;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter a supplier product name')]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter a product code')]
    private ?string $productCode = null;

    #[ORM\ManyToOne(inversedBy: 'supplierProducts')]
    #[Assert\NotBlank(message: 'Please enter a supplier')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Supplier $supplier = null;

    #[ORM\ManyToOne(inversedBy: 'supplierProducts')]
    private ?SupplierCategory $supplierCategory = null;

    #[ORM\ManyToOne(inversedBy: 'supplierProducts')]
    private ?SupplierSubcategory $supplierSubcategory = null;

    #[ORM\ManyToOne(inversedBy: 'supplierProducts')]
    private ?SupplierManufacturer $supplierManufacturer = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter a manufacturer part number')]
    private ?string $mfrPartNumber = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Please enter a weight')]
    #[Assert\Range(notInRangeMessage: 'Please enter a product weight(grams)', min: 0, max: 100000)]
    private ?int $weight = 0;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Please enter a stock level')]
    #[Assert\Range(notInRangeMessage: 'Please enter a stock level', min: 0, max: 10000)]
    private ?int $stock = 0;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Please enter a lead time')]
    #[Assert\Range(notInRangeMessage: 'Please enter a lead time(days)', min: 0, max: 1000)]
    private ?int $leadTimeDays = null;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Please enter a cost')]
    #[Assert\PositiveOrZero(message: 'Please enter a positive or zero cost')]
    private ?string $cost = '0.00';

    #[ORM\ManyToOne(inversedBy: 'supplierProducts')]
    private ?Product $product = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    final public function __construct()
    {
        $this->initializePublicId();
    }

    /**
     * @param numeric-string $cost
     */
    public static function create(
        string $name,
        string $productCode,
        SupplierCategory $supplierCategory,
        SupplierSubcategory $supplierSubcategory,
        SupplierManufacturer $supplierManufacturer,
        string $mfrPartNumber,
        int $weight,
        Supplier $supplier,
        int $stock,
        int $leadTimeDays,
        string $cost,
        ?Product $product,
        bool $isActive,
    ): self {
        $self = new self();
        $self->rename($name);
        $self->productCode = $productCode;
        $self->assignSupplierCategory($supplierCategory);
        $self->assignSupplierSubcategory($supplierSubcategory);
        $self->assignSupplierManufacturer($supplierManufacturer);
        $self->mfrPartNumber = $mfrPartNumber;
        $self->changeWeight($weight);
        $self->changePricing($supplier, $stock, $leadTimeDays, $cost, $product, $isActive);

        return $self;
    }

    /**
     * @param numeric-string $cost
     */
    public function update(
        string $name,
        string $productCode,
        SupplierCategory $supplierCategory,
        SupplierSubcategory $supplierSubcategory,
        SupplierManufacturer $supplierManufacturer,
        string $mfrPartNumber,
        int $weight,
        Supplier $supplier,
        int $stock,
        int $leadTimeDays,
        string $cost,
        ?Product $product,
        bool $isActive,
    ): void {
        $this->rename($name);
        $this->productCode = $productCode;
        $this->assignSupplierCategory($supplierCategory);
        $this->assignSupplierSubcategory($supplierSubcategory);
        $this->assignSupplierManufacturer($supplierManufacturer);
        $this->mfrPartNumber = $mfrPartNumber;
        $this->changeWeight($weight);
        $this->changePricing($supplier, $stock, $leadTimeDays, $cost, $product, $isActive);
    }

    /**
     * @param numeric-string $cost
     */
    private function changePricing(
        Supplier $supplier,
        int $stock,
        int $leadTimeDays,
        string $cost,
        ?Product $product,
        bool $isActive,
    ): void {
        $supplierChanged = $supplier !== $this->supplier;
        $stockChanged = $stock !== $this->stock;
        $leadTimeDaysChanged = $leadTimeDays !== $this->leadTimeDays;
        $costChanged = $cost !== $this->cost;
        $productChanged = $product !== $this->product;
        $isActiveChanged = $isActive !== $this->isActive;

        if (!$supplierChanged && !$stockChanged && !$leadTimeDaysChanged
            && !$costChanged && !$productChanged && !$isActiveChanged
        ) {
            return;
        }

        $previousMappedProductId = null;
        if ($this->getProduct() instanceof Product) {
            $previousMappedProductId = ProductId::fromInt($this->getProduct()->getId());
        }

        $this->assignSupplier($supplier);
        $this->updateStock($stock);
        $this->changeLeadTimeDays($leadTimeDays);
        $this->updateCost($cost);
        $this->assignProduct($product);
        $this->setActive($isActive);

        $this->raiseDomainEvent(
            new SupplierProductPricingWasChangedEvent(
                $this->getPublicId(),
                $previousMappedProductId,
            )
        );
    }

    public function assignSupplier(?Supplier $supplier): void
    {
        $this->supplier = $supplier;
    }

    public function assignSupplierCategory(?SupplierCategory $supplierCategory): void
    {
        $this->supplierCategory = $supplierCategory;
    }

    public function assignSupplierSubcategory(?SupplierSubcategory $supplierSubcategory): void
    {
        $this->supplierSubcategory = $supplierSubcategory;
    }

    public function assignSupplierManufacturer(?SupplierManufacturer $supplierManufacturer): void
    {
        $this->supplierManufacturer = $supplierManufacturer;
    }

    public function assignProduct(?Product $product): void
    {
        $this->product = $product;
    }

    public function updateStock(int $newStock): void
    {
        $stockChange = StockChange::from($this->getStock() ?? 0, $newStock);
        if (!$stockChange->hasChanged()) {
            return;
        }

        $this->stock = $newStock;
        $this->raiseDomainEvent(
            new SupplierProductStockWasChangedEvent(
                $this->getPublicId(),
                $stockChange,
                CostChange::from($this->getCost() ?? '0.00', $this->getCost() ?? '0.00')
            )
        );
    }

    /**
     * @param numeric-string $newCost
     */
    public function updateCost(string $newCost): void
    {
        $costChange = CostChange::from($this->getCost() ?? '0.00', $newCost);
        if (!$costChange->hasChanged()) {
            return;
        }

        $this->cost = $newCost;
        $this->raiseDomainEvent(
            new SupplierProductStockWasChangedEvent(
                $this->getPublicId(),
                StockChange::from($this->getStock() ?? 0, $this->getStock() ?? 0),
                $costChange,
            )
        );
    }

    public function setActive(bool $active): void
    {
        if ($this->isActive === $active) {
            return;
        }

        $this->isActive = $active;

        if (!$this->getProduct() instanceof Product) {
            return;
        }

        $this->raiseDomainEvent(
            new SupplierProductStatusWasChangedEvent($this->getPublicId())
        );
    }

    public function hasPositiveCost(): bool
    {
        return null !== $this->cost && $this->cost > 0;
    }

    public function hasActiveSupplier(): bool
    {
        return $this->supplier->isActive();
    }

    public function hasStock(): bool
    {
        return $this->stock > 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublicId(): SupplierProductPublicId
    {
        return SupplierProductPublicId::fromString($this->publicIdString());
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getProductCode(): ?string
    {
        return $this->productCode;
    }

    public function getSupplier(): Supplier
    {
        return $this->supplier ?? throw new \LogicException('Supplier must be set');
    }

    public function getSupplierCategory(): ?SupplierCategory
    {
        return $this->supplierCategory;
    }

    public function getSupplierSubcategory(): ?SupplierSubcategory
    {
        return $this->supplierSubcategory;
    }

    public function getSupplierManufacturer(): ?SupplierManufacturer
    {
        return $this->supplierManufacturer;
    }

    public function getMfrPartNumber(): ?string
    {
        return $this->mfrPartNumber;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function getLeadTimeDays(): ?int
    {
        return $this->leadTimeDays;
    }

    /**
     * @return numeric-string|null
     */
    public function getCost(): ?string
    {
        return $this->cost;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function isMapped(): bool
    {
        return $this->product instanceof Product;
    }

    private function rename(string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Product name cannot be empty');
        }

        $this->name = $name;
    }

    private function changeLeadTimeDays(int $leadTimeDays): void
    {
        if ($leadTimeDays < 0) {
            throw new \InvalidArgumentException('Lead time days cannot be negative');
        }

        $this->leadTimeDays = $leadTimeDays;
    }

    private function changeWeight(int $weight): void
    {
        if ($weight < 0) {
            throw new \InvalidArgumentException('Weight cannot be negative');
        }

        $this->weight = $weight;
    }
}
