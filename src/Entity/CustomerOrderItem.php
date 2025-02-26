<?php

namespace App\Entity;

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
    private CustomerOrder $customerOrder;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Product $product;

    #[ORM\Column]
    #[Assert\Range(notInRangeMessage: 'Quantity must be between {{ min }} and {{ max }}', min: 1, max: 10000)]
    private int $quantity = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\Range(notInRangeMessage: 'Price must be between {{ min }} and {{ max }}', min: 0, max: 10000000)]
    private string $price = '0';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\Range(notInRangeMessage: 'Price inc VAT must be between {{ min }} and {{ max }}', min: 0, max: 10000000)]
    private string $priceIncVat = '0';

    #[ORM\Column]
    #[Assert\Range(notInRangeMessage: 'Weight must be between {{ min }} and {{ max }}', min: 0, max: 10000000)]
    private int $weight = 0;

    #[ORM\Column(length: 255)]
    private OrderStatus $status;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\Range(notInRangeMessage: 'Total price must be between {{ min }} and {{ max }}', min: 0, max: 10000000)]
    private string $totalPrice = '0';

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

    private function __construct()
    {
        $this->status = OrderStatus::getDefault();
        $this->purchaseOrderItems = new ArrayCollection();
        $this->raiseDomainEvent(new OrderItemCreatedEvent($this));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomerOrder(): CustomerOrder
    {
        return $this->customerOrder;
    }

    public function setCustomerOrder(CustomerOrder $customerOrder): static
    {
        $this->customerOrder = $customerOrder;

        return $this;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    private function setProduct(Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    private function setQuantity(int $quantity): static
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('The quantity must be positive');
        }

        $this->quantity = $quantity;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    private function setPrice(string $price): static
    {
        if ((float) $price < 0) {
            throw new \InvalidArgumentException('The price must greater than 0');
        }

        $this->price = $price;

        return $this;
    }

    public function getPriceIncVat(): ?string
    {
        return $this->priceIncVat;
    }

    private function setPriceIncVat(string $priceIncVat): static
    {
        if ((float) $priceIncVat < 0) {
            throw new \InvalidArgumentException('The price inc VAT must greater than 0');
        }

        $this->priceIncVat = $priceIncVat;

        return $this;
    }

    public function getWeight(): ?int
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

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    private function setStatus(OrderStatus $status): static
    {
        if ($this->status !== $status) {
            $this->status = $status;
            $this->raiseDomainEvent(new OrderItemStatusChangedEvent($this));
        }

        return $this;
    }

    public function getTotalPrice(): ?string
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

    public function getTotalPriceIncVat(): ?string
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

    public function getTotalWeight(): ?int
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

    public function getVatRate(): VatRate
    {
        return $this->product->getCategory()->getVatRate();
    }

    public static function createFromProduct(
        CustomerOrder $customerOrder,
        Product $product,
        int $quantity = 1,
    ): static {
        $customerOrderItem = (new static())
            ->setCustomerOrder($customerOrder)
            ->setProduct($product)
            ->setQuantity($quantity)
            ->setWeight($product->getWeight())
            ->setPrice($product->getSellPrice())
            ->setPriceIncVat($product->getSellPriceIncVat());

        $customerOrderItem->getCustomerOrder()->addCustomerOrderItem($customerOrderItem);
        $customerOrderItem->recalculateTotal();
        $customerOrderItem->generateStatus();

        return $customerOrderItem;
    }

    public function updateItem(int $quantity, string $price, string $priceIncVat): static
    {
        $qtyAddedToPurchaseOrders = $this->getQtyAddedToPurchaseOrders();

        if ($qtyAddedToPurchaseOrders > $quantity) {
            throw new \LogicException('Cannot edit this allocated qty below '.$qtyAddedToPurchaseOrders);
        }

        $this->setQuantity($quantity);
        $this->setPrice($price);
        $this->setPriceIncVat($priceIncVat);
        $this->recalculateTotal();
        $this->generateStatus();

        return $this;
    }

    public function generateStatus(): void
    {
        // If the item is already cancelled, do nothing
        if (OrderStatus::CANCELLED === $this->getStatus()) {
            return;
        }

        // If there are no purchase order items, set the item status to default
        if ($this->purchaseOrderItems->isEmpty()) {
            $this->setStatus(OrderStatus::getDefault());
            $this->customerOrder->generateStatus();

            return;
        }

        // If there are still outstanding qty, set the item status to PROCESSING
        if (0 !== $this->getOutstandingQty() && 0 !== $this->getQtyAddedToPurchaseOrders()) {
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
        $this->setStatus($orderItemStatus);
        $this->customerOrder->generateStatus();
    }

    public function recalculateTotal(): void
    {
        $this->setTotalPrice(bcmul((string) $this->quantity, $this->price, 2));
        $this->setTotalPriceIncVat(bcmul((string) $this->quantity, $this->priceIncVat, 2));
        $this->setTotalWeight(bcmul((string) $this->quantity, (string) $this->weight, 3));
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
        if (0 !== $this->getQtyAddedToPurchaseOrders()) {
            throw new \LogicException('Cannot cancel this order item');
        }

        $status = OrderStatus::CANCELLED;
        if (!$this->status->canTransitionTo($status)) {
            throw new \LogicException(sprintf('Cannot transition from "%s" to "%s"', $this->status->value, $status->value));
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
