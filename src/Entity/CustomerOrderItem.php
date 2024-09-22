<?php

namespace App\Entity;

use App\Entity\Interfaces\DomainEventProviderInterface;
use App\Entity\Traits\DomainEventTrait;
use App\Enum\OrderStatus;
use App\Enum\PurchaseOrderStatus;
use App\Event\OrderItemCreatedEvent;
use App\Event\OrderItemStatusChangedEvent;
use App\Repository\CustomerOrderItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;
use function PHPUnit\Framework\assertInstanceOf;

#[ORM\Entity(repositoryClass: CustomerOrderItemRepository::class)]
class CustomerOrderItem implements DomainEventProviderInterface
{
    use TimestampableEntity;
    use DomainEventTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'customerOrderItems')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Please enter a customer order')]
    private ?CustomerOrder $customerOrder = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Please enter a product')]
    private ?Product $product = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Please enter a product quantity')]
    #[Assert\Range(notInRangeMessage: 'Please enter a product quantity (0 to 100000)', min: 0, max: 100000)]
    private int $quantity = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Please enter a product price')]
    #[Assert\Range(notInRangeMessage: 'Please enter a product price (0 to 100000)', min: 0, max: 100000)]
    private string $price = '0';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Please enter a product price including VAT')]
    #[Assert\Range(notInRangeMessage: 'Please enter a product price inc VAT (0 to 100000)', min: 0, max: 100000)]
    private string $priceIncVat = '0';

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Please enter a product weight')]
    #[Assert\Range(notInRangeMessage: 'Please enter a product weight (0 to 100000)', min: 0, max: 100000)]
    private int $weight = 0;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter a status')]
    private OrderStatus $status;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $totalPrice = '0';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $totalPriceIncVat = '0';

    #[ORM\Column]
    private int $totalWeight = 0;

    /**
     * @var Collection<int, PurchaseOrderItem>
     */
    #[ORM\OneToMany(targetEntity: PurchaseOrderItem::class, mappedBy: 'customerOrderItem')]
    private Collection $purchaseOrderItems;

    public function __construct()
    {
        $this->status = OrderStatus::getDefault();
        $this->purchaseOrderItems = new ArrayCollection();
        $this->raiseDomainEvent(new OrderItemCreatedEvent($this));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomerOrder(): ?CustomerOrder
    {
        return $this->customerOrder;
    }

    public function setCustomerOrder(?CustomerOrder $customerOrder): static
    {
        $this->customerOrder = $customerOrder;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

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

    private function setStatus(OrderStatus $status): void
    {
        if ($this->status === $status) {
            return;
        }

        $this->status = $status;
        $this->raiseDomainEvent(new OrderItemStatusChangedEvent($this));
    }

    public function getTotalPrice(): ?string
    {
        return $this->totalPrice;
    }

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

    public function createFromProduct(Product $product, int $quantity = 1): static
    {
        $this->product = $product;
        $this->quantity = $quantity;
        $this->weight = $product->getWeight();
        $this->price = $product->getSellPrice();
        $this->priceIncVat = $product->getSellPriceIncVat();
        $this->recalculateTotal();
        $this->generateStatus();

        return $this;
    }

    public function updateItem(int $quantity, string $price, string $priceIncVat): static
    {
        $qtyAddedToPurchaseOrders = $this->getQtyAddedToPurchaseOrders();

        if ($qtyAddedToPurchaseOrders > $quantity) {
            throw new \LogicException('Cannot edit this allocated qty below %s', $qtyAddedToPurchaseOrders);
        }

        $this->quantity = $quantity;
        $this->price = $price;
        $this->priceIncVat = $priceIncVat;
        $this->recalculateTotal();
        $this->generateStatus();

        return $this;
    }

    public function generateStatus(): void
    {
        // If the item is already cancelled, do nothing
        if ($this->getStatus() === OrderStatus::CANCELLED) {
            return;
        }

        // If there are no purchase order items, set the item status to default
        if ($this->purchaseOrderItems->isEmpty()) {
            $this->setStatus(OrderStatus::getDefault());
            $this->customerOrder->generateStatus();

            return;
        }

        // If there are still outstanding qty, set the item status to PROCESSING
        if ($this->getOutstandingQty() !== 0) {
            $this->setStatus(OrderStatus::PROCESSING);
            $this->customerOrder->generateStatus();

            return;
        }

        $this->updateStatusBasedOnPurchaseOrderItems();
    }

    private function updateStatusBasedOnPurchaseOrderItems(): void
    {
        $purchaseOrderItemStatus = null;
        foreach ($this->purchaseOrderItems as $item) {
            // Skip refunded items
            if ($item->getStatus() === PurchaseOrderStatus::REFUNDED) {
                continue;
            }

            if ($purchaseOrderItemStatus === null || $item->getStatus()->getLevel() < $purchaseOrderItemStatus->getLevel()) {
                $purchaseOrderItemStatus = $item->getStatus();
            }
        }

        // If all items are refunded, set the purchase order item status to default
        if ($purchaseOrderItemStatus === null) {
            $purchaseOrderItemStatus = PurchaseOrderStatus::getDefault();
        }

        $orderItemStatus = OrderStatus::getMappedOrderStatusFromPurchaseOrder($purchaseOrderItemStatus);
        $this->setStatus($orderItemStatus);
        $this->customerOrder->generateStatus();
    }


    public function recalculateTotal(): void
    {
        $this->totalPrice = bcmul((string) $this->quantity, $this->price, 2);
        $this->totalPriceIncVat = bcmul((string) $this->quantity, $this->priceIncVat, 2);
        $this->totalWeight = bcmul((string)$this->quantity, (string) $this->weight, 3);
    }

    public function getOutstandingQty(): int
    {
        return max(($this->getQuantity() - $this->getQtyAddedToPurchaseOrders()), 0);
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

    public function allowEdit(): bool
    {
        return $this->status->allowEdit();
    }

    public function allowCancel(): bool
    {
        return $this->getQtyAddedToPurchaseOrders() === 0
            && ($this->getStatus() === OrderStatus::PENDING || $this->getStatus() === OrderStatus::PROCESSING);
    }

    public function cancelItem(): void
    {
        if ($this->getQtyAddedToPurchaseOrders() !== 0) {
            throw new \LogicException('Cannot cancel this order item');
        }

        $status = OrderStatus::CANCELLED;
        if (!$this->status->canTransitionTo($status)) {
            throw new \LogicException(sprintf('Cannot transition from "%s" to "%s"',
                $this->status->value,
                $status->value
            ));
        }

        $this->setStatus($status);
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
            $purchaseOrderItem->setCustomerOrderItem($this);

            $this->generateStatus();
        }

        return $this;
    }

    public function removePurchaseOrderItem(PurchaseOrderItem $purchaseOrderItem): static
    {
        if ($this->purchaseOrderItems->removeElement($purchaseOrderItem)) {
            // set the owning side to null (unless already changed)
            if ($purchaseOrderItem->getCustomerOrderItem() === $this) {
                $purchaseOrderItem->setCustomerOrderItem(null);
            }

            $this->generateStatus();
        }

        return $this;
    }
}
