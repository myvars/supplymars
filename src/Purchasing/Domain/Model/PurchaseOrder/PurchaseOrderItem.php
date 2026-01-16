<?php

namespace App\Purchasing\Domain\Model\PurchaseOrder;

use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\Event\PurchaseOrderItemStatusWasChangedEvent;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\Infrastructure\Persistence\Doctrine\PurchaseOrderItemDoctrineRepository;
use App\Shared\Domain\Event\DomainEventProviderInterface;
use App\Shared\Domain\Event\DomainEventProviderTrait;
use App\Shared\Domain\ValueObject\StatusChange;
use App\Shared\Infrastructure\Persistence\Doctrine\Mapping\HasPublicUlid;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PurchaseOrderItemDoctrineRepository::class)]
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

    public static function createFromCustomerOrderItem(
        CustomerOrderItem $customerOrderItem,
        PurchaseOrder $purchaseOrder,
        SupplierProduct $supplierProduct,
        int $quantity,
    ): static {
        $self = new self();
        $self->assignCustomerOrderItem($customerOrderItem);
        $self->assignPurchaseOrder($purchaseOrder);
        $self->supplierProduct = $supplierProduct;
        $self->changePrice($supplierProduct->getCost());
        $self->changePriceIncVat($supplierProduct->getCost());
        $self->changeWeight($supplierProduct->getWeight());
        $self->changeQuantity($quantity);

        $self->getPurchaseOrder()->addPurchaseOrderItem($self);
        $self->getCustomerOrderItem()->addPurchaseOrderItem($self);
        $self->recalculateTotal();

        return $self;
    }

    public function updateItemQuantity(int $quantity): void
    {
        if (!$this->allowEdit()) {
            throw new \LogicException('Purchase order item cannot be edited');
        }

        if ($quantity === $this->quantity) {
            return;
        }

        $this->changeQuantity($quantity);
        $this->recalculateTotal();
    }

    public function updateItemStatus(PurchaseOrderStatus $newStatus): void
    {
        if (!$this->allowStatusChange()) {
            throw new \LogicException('Status cannot be changed');
        }

        $this->changeStatus($newStatus);
    }

    public function assignPurchaseOrder(PurchaseOrder $purchaseOrder): void
    {
        $this->purchaseOrder = $purchaseOrder;
    }

    public function assignCustomerOrderItem(?CustomerOrderItem $customerOrderItem): void
    {
        $this->customerOrderItem = $customerOrderItem;
    }

    public function allowEdit(): bool
    {
        return $this->status->allowEdit();
    }

    public function allowStatusChange(): bool
    {
        return PurchaseOrderStatus::DELIVERED !== $this->status && PurchaseOrderStatus::CANCELLED !== $this->status;
    }

    public function recalculateTotal(): void
    {
        $this->changeTotalPrice(bcmul((string) $this->quantity, $this->price, 2));
        $this->changeTotalPriceIncVat(bcmul((string) $this->quantity, $this->priceIncVat, 2));
        $this->changeTotalWeight(bcmul((string) $this->quantity, (string) $this->weight, 3));
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

    public function getSupplierProduct(): SupplierProduct
    {
        return $this->supplierProduct;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getMaxQuantity(): int
    {
        return $this->getCustomerOrderItem()->getOutstandingQty() + $this->getQuantity();
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function getPriceIncVat(): string
    {
        return $this->priceIncVat;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function getStatus(): PurchaseOrderStatus
    {
        return $this->status;
    }

    public function getCustomerOrderItem(): ?CustomerOrderItem
    {
        return $this->customerOrderItem;
    }

    public function getTotalPrice(): string
    {
        return $this->totalPrice;
    }

    public function getTotalPriceIncVat(): string
    {
        return $this->totalPriceIncVat;
    }

    public function getTotalWeight(): int
    {
        return $this->totalWeight;
    }

    public function getDeliveredAt(): ?\DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    public function isRefunded(): bool
    {
        return $this->status->isRefunded();
    }

    public function isCancelled(): bool
    {
        return $this->status->isCancelled();
    }

    private function changeQuantity(int $quantity): void
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('The quantity must be greater than 0');
        }

        if ($quantity > $this->getMaxQuantity()) {
            throw new \InvalidArgumentException('Quantity cannot be greater than ' . $this->getMaxQuantity());
        }

        $this->quantity = $quantity;
    }

    private function changePrice(string $price): void
    {
        if ((float) $price < 0) {
            throw new \InvalidArgumentException('The price must be greater than 0');
        }

        $this->price = $price;
    }

    private function changePriceIncVat(string $priceIncVat): void
    {
        if ((float) $priceIncVat < 0) {
            throw new \InvalidArgumentException('The price inc VAT must be greater than 0');
        }

        $this->priceIncVat = $priceIncVat;
    }

    private function changeWeight(int $weight): void
    {
        if ($weight < 0) {
            throw new \InvalidArgumentException('The weight must be greater than 0');
        }

        $this->weight = $weight;
    }

    private function changeTotalPrice(string $totalPrice): void
    {
        if ((float) $totalPrice < 0) {
            throw new \InvalidArgumentException('The total price must be greater than 0');
        }

        $this->totalPrice = $totalPrice;
    }

    private function changeTotalPriceIncVat(string $totalPriceIncVat): void
    {
        if ((float) $totalPriceIncVat < 0) {
            throw new \InvalidArgumentException('The total price inc VAT must be greater than 0');
        }

        $this->totalPriceIncVat = $totalPriceIncVat;
    }

    private function changeTotalWeight(int $totalWeight): void
    {
        if ($totalWeight < 0) {
            throw new \InvalidArgumentException('The total weight must be greater than 0');
        }

        $this->totalWeight = $totalWeight;
    }

    private function recordDelivery(?\DateTimeImmutable $deliveredAt): void
    {
        $this->deliveredAt = $deliveredAt;
    }

    private function changeStatus(PurchaseOrderStatus $newStatus): void
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
            $this->recordDelivery(new \DateTimeImmutable());
        }

        $this->getPurchaseOrder()->generateStatus();
        $this->getCustomerOrderItem()->generateStatus();

        $this->raiseDomainEvent(
            new PurchaseOrderItemStatusWasChangedEvent($this->getPublicId(), $statusChange)
        );
    }
}
