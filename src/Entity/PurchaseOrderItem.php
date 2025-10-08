<?php

namespace App\Entity;

use App\Enum\PurchaseOrderStatus;
use App\Event\PurchaseOrderItemStatusWasChangedEvent;
use App\Repository\PurchaseOrderItemRepository;
use App\ValueObject\PurchaseOrderItemPublicId;
use App\ValueObject\StatusChange;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PurchaseOrderItemRepository::class)]
class PurchaseOrderItem implements DomainEventProviderInterface
{
    use TimestampableEntity;
    use DomainEventProviderTrait;
    use HasPublicUlid;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'purchaseOrderItems')]
    #[ORM\JoinColumn(nullable: false)]
    private PurchaseOrder $purchaseOrder;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private SupplierProduct $supplierProduct;

    #[ORM\Column]
    #[Assert\Range(notInRangeMessage: 'Quantity must be between {{ min }} and {{ max }}', min: 1, max: 10000)]
    private int $quantity = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $price = '0';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $priceIncVat = '0';

    #[ORM\Column]
    private int $weight = 0;

    #[ORM\Column(length: 255)]
    private PurchaseOrderStatus $status;

    #[ORM\ManyToOne(inversedBy: 'purchaseOrderItems')]
    private ?CustomerOrderItem $customerOrderItem = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $totalPrice = '0';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $totalPriceIncVat = '0';

    #[ORM\Column]
    private int $totalWeight = 0;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deliveredAt = null;

    private function __construct()
    {
        $this->initializePublicId();
        $this->status = PurchaseOrderStatus::getDefault();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublicId(): PurchaseOrderItemPublicId
    {
        return PurchaseOrderItemPublicId::fromString($this->publicIdString());
    }

    public function getPurchaseOrder(): PurchaseOrder
    {
        return $this->purchaseOrder;
    }

    public function setPurchaseOrder(PurchaseOrder $purchaseOrder): static
    {
        $this->purchaseOrder = $purchaseOrder;

        return $this;
    }

    public function getSupplierProduct(): SupplierProduct
    {
        return $this->supplierProduct;
    }

    private function setSupplierProduct(?SupplierProduct $supplierProduct): static
    {
        $this->supplierProduct = $supplierProduct;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    private function setQuantity(int $quantity): static
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('The quantity must be greater than 0');
        }

        if ($quantity > $this->getMaxQuantity()) {
            throw new \InvalidArgumentException('Quantity cannot be greater than '.$this->getMaxQuantity());
        }

        $this->quantity = $quantity;

        return $this;
    }

    public function getMaxQuantity(): int
    {
        return $this->getCustomerOrderItem()->getOutstandingQty() + $this->getQuantity();
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    private function setPrice(string $price): static
    {
        if ((float) $price < 0) {
            throw new \InvalidArgumentException('The price must be greater than 0');
        }

        $this->price = $price;

        return $this;
    }

    public function getPriceIncVat(): string
    {
        return $this->priceIncVat;
    }

    private function setPriceIncVat(string $priceIncVat): static
    {
        if ((float) $priceIncVat < 0) {
            throw new \InvalidArgumentException('The price inc VAT must be greater than 0');
        }

        $this->priceIncVat = $priceIncVat;

        return $this;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    private function setWeight(int $weight): static
    {
        if ($weight < 0) {
            throw new \InvalidArgumentException('The weight must be greater than 0');
        }

        $this->weight = $weight;

        return $this;
    }

    public function getStatus(): PurchaseOrderStatus
    {
        return $this->status;
    }

    public function getCustomerOrderItem(): ?CustomerOrderItem
    {
        return $this->customerOrderItem;
    }

    public function setCustomerOrderItem(?CustomerOrderItem $customerOrderItem): static
    {
        $this->customerOrderItem = $customerOrderItem;

        return $this;
    }

    public function getTotalPrice(): string
    {
        return $this->totalPrice;
    }

    private function setTotalPrice(string $totalPrice): static
    {
        if ((float) $totalPrice < 0) {
            throw new \InvalidArgumentException('The total price must be greater than 0');
        }

        $this->totalPrice = $totalPrice;

        return $this;
    }

    public function getTotalPriceIncVat(): string
    {
        return $this->totalPriceIncVat;
    }

    private function setTotalPriceIncVat(string $totalPriceIncVat): static
    {
        if ((float) $totalPriceIncVat < 0) {
            throw new \InvalidArgumentException('The total price inc VAT must be greater than 0');
        }

        $this->totalPriceIncVat = $totalPriceIncVat;

        return $this;
    }

    public function getTotalWeight(): int
    {
        return $this->totalWeight;
    }

    private function setTotalWeight(int $totalWeight): static
    {
        if ($totalWeight < 0) {
            throw new \InvalidArgumentException('The total weight must be greater than 0');
        }

        $this->totalWeight = $totalWeight;

        return $this;
    }

    public function getDeliveredAt(): ?\DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    private function setDeliveredAt(?\DateTimeImmutable $deliveredAt): static
    {
        $this->deliveredAt = $deliveredAt;

        return $this;
    }

    public static function createFromCustomerOrderItem(
        CustomerOrderItem $customerOrderItem,
        PurchaseOrder $purchaseOrder,
        SupplierProduct $supplierProduct,
        int $quantity,
    ): static {
        $purchaseOrderItem = new static()
            ->setCustomerOrderItem($customerOrderItem)
            ->setPurchaseOrder($purchaseOrder)
            ->setSupplierProduct($supplierProduct)
            ->setPrice($supplierProduct->getCost())
            ->setPriceIncVat($supplierProduct->getCost())
            ->setWeight($supplierProduct->getWeight())
            ->setQuantity($quantity);

        $purchaseOrderItem->getPurchaseOrder()->addPurchaseOrderItem($purchaseOrderItem);
        $purchaseOrderItem->getCustomerOrderItem()->addPurchaseOrderItem($purchaseOrderItem);
        $purchaseOrderItem->recalculateTotal();

        return $purchaseOrderItem;
    }

    public function updateItem(int $quantity): void
    {
        $this->setQuantity($quantity);
        $this->recalculateTotal();
    }

    public function recalculateTotal(): void
    {
        $this->setTotalPrice(bcmul((string) $this->quantity, $this->price, 2));
        $this->setTotalPriceIncVat(bcmul((string) $this->quantity, $this->priceIncVat, 2));
        $this->setTotalWeight(bcmul((string) $this->quantity, (string) $this->weight, 3));
    }

    public function updateStatus(PurchaseOrderStatus $newStatus): void
    {
        $statusChange = StatusChange::from($this->getStatus(), $newStatus);
        if (!$statusChange->hasChanged()) {
            return;
        }

        if (!$this->status->canTransitionTo($newStatus)) {
            throw new \LogicException(sprintf('Cannot transition from "%s" to "%s"', $this->status->value, $newStatus->value));
        }

        $this->status = $newStatus;
        if (PurchaseOrderStatus::DELIVERED === $newStatus) {
            $this->setDeliveredAt(new \DateTimeImmutable());
        }

        $this->raiseDomainEvent(new PurchaseOrderItemStatusWasChangedEvent($this->getPublicId(), $statusChange));

        $this->getPurchaseOrder()->generateStatus();
        $this->getCustomerOrderItem()->generateStatus();
    }

    public function allowEdit(): bool
    {
        return $this->status->allowEdit();
    }

    public function allowStatusChange(): bool
    {
        return PurchaseOrderStatus::DELIVERED !== $this->status && PurchaseOrderStatus::CANCELLED !== $this->status;
    }

    public function isRefunded(): bool
    {
        return $this->status->isRefunded();
    }

    public function isCancelled(): bool
    {
        return $this->status->isCancelled();
    }
}
