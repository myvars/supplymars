<?php

namespace App\Order\Domain\Model\Order;

use App\Catalog\Domain\Model\Product\Product;
use App\Order\Domain\Model\Order\Event\OrderItemStatusWasChangedEvent;
use App\Order\Infrastructure\Persistence\Doctrine\CustomerOrderItemDoctrineRepository;
use App\Pricing\Domain\Model\VatRate\VatRate;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Shared\Domain\Event\DomainEventProviderInterface;
use App\Shared\Domain\Event\DomainEventProviderTrait;
use App\Shared\Domain\ValueObject\StatusChange;
use App\Shared\Infrastructure\Persistence\Doctrine\Mapping\HasPublicUlid;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CustomerOrderItemDoctrineRepository::class)]
class CustomerOrderItem implements DomainEventProviderInterface
{
    use TimestampableEntity;
    use DomainEventProviderTrait;
    use HasPublicUlid;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'customerOrderItems')]
    #[ORM\JoinColumn(nullable: false)]
    private CustomerOrder $customerOrder;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Product $product;

    #[ORM\Column]
    #[Assert\Range(notInRangeMessage: 'Quantity must be between {{ min }} and {{ max }}', min: 1, max: 10000)]
    private int $quantity = 0;

    /** @var numeric-string */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\Range(notInRangeMessage: 'Price must be between {{ min }} and {{ max }}', min: 0, max: 10000000)]
    private string $price = '0';

    /** @var numeric-string */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\Range(notInRangeMessage: 'Price inc VAT must be between {{ min }} and {{ max }}', min: 0, max: 10000000)]
    private string $priceIncVat = '0';

    #[ORM\Column]
    #[Assert\Range(notInRangeMessage: 'Weight must be between {{ min }} and {{ max }}', min: 0, max: 10000000)]
    private int $weight = 0;

    #[ORM\Column(length: 255)]
    private OrderStatus $status;

    /** @var numeric-string */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\Range(notInRangeMessage: 'Total price must be between {{ min }} and {{ max }}', min: 0, max: 10000000)]
    private string $totalPrice = '0';

    /** @var numeric-string */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\Range(
        notInRangeMessage: 'Total price inc VAT must be between {{ min }} and {{ max }}', min: 0, max: 10000000
    )]
    private string $totalPriceIncVat = '0';

    #[ORM\Column]
    #[Assert\Range(notInRangeMessage: 'Total weight must be between {{ min }} and {{ max }}', min: 0, max: 100000000)]
    private int $totalWeight = 0;

    /**
     * @var Collection<int, PurchaseOrderItem>
     */
    #[ORM\OneToMany(targetEntity: PurchaseOrderItem::class, mappedBy: 'customerOrderItem')]
    private Collection $purchaseOrderItems;

    final private function __construct()
    {
        $this->initializePublicId();
        $this->status = OrderStatus::getDefault();
        $this->purchaseOrderItems = new ArrayCollection();
    }

    public static function createFromProduct(
        CustomerOrder $customerOrder,
        Product $product,
        int $quantity = 1,
    ): static {
        $static = new static();
        $static->customerOrder = $customerOrder;
        $static->product = $product;
        $static->changeQuantity($quantity);
        $static->changeWeight($product->getWeight());
        $static->changePrice($product->getSellPrice());
        $static->changePriceIncVat($product->getSellPriceIncVat());

        $static->getCustomerOrder()->addCustomerOrderItem($static);
        $static->recalculateTotal();
        $static->generateStatus();

        return $static;
    }

    /**
     * @param numeric-string $price
     * @param numeric-string $priceIncVat
     */
    public function updateItem(
        int $quantity,
        string $price,
        string $priceIncVat,
        int $weight,
    ): void {
        $qtyAddedToPurchaseOrders = $this->getQtyAddedToPurchaseOrders();
        if ($qtyAddedToPurchaseOrders > $quantity) {
            throw new \LogicException('Cannot edit this allocated qty below ' . $qtyAddedToPurchaseOrders);
        }

        $this->changeQuantity($quantity);
        $this->changePrice($price);
        $this->changePriceIncVat($priceIncVat);
        $this->changeWeight($weight);

        $this->recalculateTotal();
        $this->generateStatus();

        // $this->customerOrder->recalculateTotal();
    }

    public function assignCustomerOrder(CustomerOrder $customerOrder): void
    {
        $this->customerOrder = $customerOrder;
    }

    public function allowEdit(): bool
    {
        return $this->status->allowEdit();
    }

    public function allowCancel(): bool
    {
        return 0 === $this->getQtyAddedToPurchaseOrders()
            && (OrderStatus::PENDING === $this->getStatus() || OrderStatus::PROCESSING === $this->getStatus());
    }

    public function cancelItem(): void
    {
        if ($this->isCancelled()) {
            return;
        }

        if (0 !== $this->getQtyAddedToPurchaseOrders()) {
            throw new \LogicException('Cannot cancel this order item');
        }

        $status = OrderStatus::CANCELLED;
        if (!$this->status->canTransitionTo($status)) {
            throw new \LogicException(sprintf('Cannot transition from "%s" to "%s"', $this->status->value, $status->value));
        }

        $this->changeStatus($status);
    }

    public function generateStatus(): void
    {
        // If the item is already cancelled, do nothing
        if (OrderStatus::CANCELLED === $this->getStatus()) {
            return;
        }

        // If there are no purchase order items, set the item status to default
        if ($this->purchaseOrderItems->isEmpty()) {
            $this->changeStatus(OrderStatus::getDefault());
            $this->customerOrder->generateStatus();

            return;
        }

        // If there are still outstanding qty, set the item status to PROCESSING
        if (0 !== $this->getOutstandingQty() && 0 !== $this->getQtyAddedToPurchaseOrders()) {
            $this->changeStatus(OrderStatus::PROCESSING);
            $this->customerOrder->generateStatus();

            return;
        }

        $this->updateStatusBasedOnPurchaseOrderItems();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublicId(): OrderItemPublicId
    {
        return OrderItemPublicId::fromString($this->publicIdString());
    }

    public function getCustomerOrder(): CustomerOrder
    {
        return $this->customerOrder;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @return numeric-string|null
     */
    public function getPrice(): ?string
    {
        return $this->price;
    }

    /**
     * @return numeric-string|null
     */
    public function getPriceIncVat(): ?string
    {
        return $this->priceIncVat;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    /**
     * @return numeric-string|null
     */
    public function getTotalPrice(): ?string
    {
        return $this->totalPrice;
    }

    /**
     * @return numeric-string|null
     */
    public function getTotalPriceIncVat(): ?string
    {
        return $this->totalPriceIncVat;
    }

    public function getTotalWeight(): ?int
    {
        return $this->totalWeight;
    }

    public function getVatRate(): VatRate
    {
        return $this->product->getCategory()->getVatRate();
    }

    public function getOutstandingQty(): int
    {
        return max($this->getQuantity() - $this->getQtyAddedToPurchaseOrders(), 0);
    }

    public function getQtyAddedToPurchaseOrders(): int
    {
        $quantity = 0;
        foreach ($this->getPurchaseOrderItems() as $purchaseOrderItem) {
            if (!$purchaseOrderItem->isCancelled() && !$purchaseOrderItem->isRefunded()) {
                $quantity += $purchaseOrderItem->getQuantity();
            }
        }

        return $quantity;
    }

    public function isCancelled(): bool
    {
        return OrderStatus::CANCELLED === $this->getStatus();
    }

    private function changeQuantity(int $quantity): void
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('The quantity must be positive');
        }

        $this->quantity = $quantity;
    }

    /**
     * @param numeric-string $price
     */
    private function changePrice(string $price): void
    {
        if ((float) $price < 0) {
            throw new \InvalidArgumentException('The price must greater than 0');
        }

        $this->price = $price;
    }

    /**
     * @param numeric-string $priceIncVat
     */
    private function changePriceIncVat(string $priceIncVat): void
    {
        if ((float) $priceIncVat < 0) {
            throw new \InvalidArgumentException('The price inc VAT must greater than 0');
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

    private function changeStatus(OrderStatus $newStatus): void
    {
        $statusChange = StatusChange::from($this->getStatus(), $newStatus);

        if ($statusChange->hasChanged()) {
            $this->status = $newStatus;

            $this->raiseDomainEvent(new OrderItemStatusWasChangedEvent($this->getPublicId(), $statusChange));
        }
    }

    /**
     * @param numeric-string $totalPrice
     */
    private function changeTotalPrice(string $totalPrice): void
    {
        if ((float) $totalPrice < 0) {
            throw new \InvalidArgumentException('The total price must be greater than 0');
        }

        $this->totalPrice = $totalPrice;
    }

    /**
     * @param numeric-string $totalPriceIncVat
     */
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

    private function recalculateTotal(): void
    {
        $this->changeTotalPrice(bcmul((string) $this->quantity, $this->price, 2));
        $this->changeTotalPriceIncVat(bcmul((string) $this->quantity, $this->priceIncVat, 2));
        $this->changeTotalWeight((int) bcmul((string) $this->quantity, (string) $this->weight, 3));
    }

    private function updateStatusBasedOnPurchaseOrderItems(): void
    {
        $purchaseOrderItemStatus = null;
        foreach ($this->purchaseOrderItems as $item) {
            // Skip refunded items
            if (PurchaseOrderStatus::REFUNDED === $item->getStatus()) {
                continue;
            }

            if (null === $purchaseOrderItemStatus || $item->getStatus()->getLevel() < $purchaseOrderItemStatus->getLevel()) {
                $purchaseOrderItemStatus = $item->getStatus();
            }
        }

        // If all items are refunded, set the purchase order item status to default
        if (null === $purchaseOrderItemStatus) {
            $purchaseOrderItemStatus = PurchaseOrderStatus::getDefault();
        }

        $orderItemStatus = OrderStatus::getMappedOrderStatusFromPurchaseOrder($purchaseOrderItemStatus);
        $this->changeStatus($orderItemStatus);
        $this->customerOrder->generateStatus();
    }

    /**
     * @return Collection<int, PurchaseOrderItem>
     */
    public function getPurchaseOrderItems(): Collection
    {
        return $this->purchaseOrderItems;
    }

    public function addPurchaseOrderItem(PurchaseOrderItem $purchaseOrderItem): static
    {
        if (!$this->purchaseOrderItems->contains($purchaseOrderItem)) {
            $this->purchaseOrderItems->add($purchaseOrderItem);
            $purchaseOrderItem->assignCustomerOrderItem($this);

            $this->generateStatus();
        }

        return $this;
    }

    public function removePurchaseOrderItem(PurchaseOrderItem $purchaseOrderItem): static
    {
        if ($this->purchaseOrderItems->removeElement($purchaseOrderItem)) {
            // set the owning side to null (unless already changed)
            if ($purchaseOrderItem->getCustomerOrderItem() === $this) {
                $purchaseOrderItem->assignCustomerOrderItem(null);
            }

            $this->generateStatus();
        }

        return $this;
    }
}
