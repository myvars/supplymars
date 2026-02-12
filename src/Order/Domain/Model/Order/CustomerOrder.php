<?php

namespace App\Order\Domain\Model\Order;

use App\Customer\Domain\Model\Address\Address;
use App\Customer\Domain\Model\User\User;
use App\Order\Domain\Model\Order\Event\OrderStatusWasChangedEvent;
use App\Order\Infrastructure\Persistence\Doctrine\CustomerOrderDoctrineRepository;
use App\Pricing\Domain\Model\VatRate\VatRate;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Shared\Domain\Event\DomainEventProviderInterface;
use App\Shared\Domain\Event\DomainEventProviderTrait;
use App\Shared\Domain\ValueObject\ShippingMethod;
use App\Shared\Domain\ValueObject\StatusChange;
use App\Shared\Infrastructure\Persistence\Doctrine\Mapping\HasPublicUlid;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CustomerOrderDoctrineRepository::class)]
#[ORM\Index(name: 'idx_customer_order_status_created', columns: ['status', 'created_at'])]
class CustomerOrder implements DomainEventProviderInterface
{
    use TimestampableEntity;
    use DomainEventProviderTrait;
    use HasPublicUlid;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'customerOrders')]
    #[ORM\JoinColumn(nullable: false)]
    private User $customer;

    #[ORM\ManyToOne(inversedBy: 'customerOrders')]
    #[ORM\JoinColumn(nullable: false)]
    private Address $shippingAddress;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Address $billingAddress;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customerOrderRef = null;

    #[ORM\Column(length: 255)]
    #[ORM\JoinColumn(nullable: false)]
    private ShippingMethod $shippingMethod;

    #[ORM\Column]
    private \DateTimeImmutable $dueDate;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\Range(notInRangeMessage: 'Shipping price must be between {{ min }} and {{ max }}', min: 0, max: 10000)]
    private string $shippingPrice = '0';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\Range(
        notInRangeMessage: 'Shipping price int VAT must be between {{ min }} and {{ max }}', min: 0, max: 10000
    )]
    private string $shippingPriceIncVat = '0';

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
     * @var Collection<int, CustomerOrderItem>
     */
    #[ORM\OneToMany(targetEntity: CustomerOrderItem::class, mappedBy: 'customerOrder')]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $customerOrderItems;

    /**
     * @var Collection<int, PurchaseOrder>
     */
    #[ORM\OneToMany(targetEntity: PurchaseOrder::class, mappedBy: 'customerOrder')]
    private Collection $purchaseOrders;

    #[ORM\ManyToOne]
    private ?User $orderLock = null;

    final private function __construct()
    {
        $this->initializePublicId();
        $this->status = OrderStatus::getDefault();
        $this->customerOrderItems = new ArrayCollection();
        $this->purchaseOrders = new ArrayCollection();
    }

    public static function createFromCustomer(
        User $customer,
        ShippingMethod $shippingMethod,
        VatRate $vatRate,
        ?string $customerOrderRef,
    ): self {
        $self = new self();
        $self->assignCustomer($customer);
        $self->shippingAddress = $customer->getShippingAddress();
        $self->billingAddress = $customer->getBillingAddress();
        $self->customerOrderRef = $customerOrderRef;
        $self->shippingMethod = $shippingMethod;
        $self->changeShippingPrice($shippingMethod->getPrice());
        $self->changeShippingPriceIncVat($shippingMethod->getPriceIncVat($vatRate));
        $self->dueDate = $shippingMethod->getDueDate();

        $self->recalculateTotal();

        return $self;
    }

    public function assignCustomer(User $customer): void
    {
        $this->customer = $customer;
    }

    public function assignShippingAddress(Address $shippingAddress): void
    {
        $this->shippingAddress = $shippingAddress;
    }

    public function recalculateTotal(): void
    {
        $totalPrice = 0;
        $totalPriceIncVat = 0;
        $totalWeight = 0;

        foreach ($this->customerOrderItems as $customerOrderItem) {
            if (OrderStatus::CANCELLED === $customerOrderItem->getStatus()) {
                continue;
            }

            $totalPrice += $customerOrderItem->getTotalPrice();
            $totalPriceIncVat += $customerOrderItem->getTotalPriceIncVat();
            $totalWeight += $customerOrderItem->getTotalWeight();
        }

        if (OrderStatus::CANCELLED !== $this->getStatus()) {
            $totalPrice += (float) $this->getShippingPrice();
            $totalPriceIncVat += (float) $this->getShippingPriceIncVat();
        }

        $this->changeTotalPrice((string) $totalPrice);
        $this->changeTotalPriceIncVat((string) $totalPriceIncVat);
        $this->changeTotalWeight($totalWeight);
    }

    public function generateStatus(): void
    {
        $orderStatus = null;
        foreach ($this->customerOrderItems as $item) {
            if (null === $orderStatus || $item->getStatus()->getLevel() < $orderStatus->getLevel()) {
                $orderStatus = $item->getStatus();
            }
        }

        if (null === $orderStatus) {
            $orderStatus = OrderStatus::getDefault();
        }

        $this->changeStatus($orderStatus);
        $this->recalculateTotal();
    }

    public function cancelOrder(): void
    {
        if ($this->isCancelled()) {
            throw new \LogicException('Order already cancelled');
        }

        if (!$this->allowCancel()) {
            throw new \LogicException('Order cannot be cancelled');
        }

        foreach ($this->getCustomerOrderItems() as $item) {
            if (!$item->isCancelled() && !$item->allowCancel()) {
                throw new \LogicException('Items cannot be cancelled');
            }
        }

        foreach ($this->getCustomerOrderItems() as $item) {
            $item->cancelItem();
        }

        $this->changeStatus(OrderStatus::CANCELLED);
        $this->generateStatus();
    }

    public function allowEdit(): bool
    {
        return $this->status->allowEdit();
    }

    public function allowCancel(): bool
    {
        return $this->status->allowCancel();
    }

    public function lockOrder(?User $user): void
    {
        $this->orderLock = $user;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublicId(): OrderPublicId
    {
        return OrderPublicId::fromString($this->publicIdString());
    }

    public function getCustomer(): User
    {
        return $this->customer;
    }

    public function getShippingAddress(): Address
    {
        return $this->shippingAddress;
    }

    public function getBillingAddress(): Address
    {
        return $this->billingAddress;
    }

    public function getCustomerOrderRef(): ?string
    {
        return $this->customerOrderRef;
    }

    public function getShippingMethod(): ShippingMethod
    {
        return $this->shippingMethod;
    }

    public function getDueDate(): \DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function getShippingPrice(): string
    {
        return $this->shippingPrice;
    }

    public function getShippingPriceIncVat(): string
    {
        return $this->shippingPriceIncVat;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
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

    public function getOrderLock(): ?User
    {
        return $this->orderLock;
    }

    public function getLineCount(): int
    {
        return $this->customerOrderItems->count();
    }

    public function getItemCount(): int
    {
        $count = 0;
        foreach ($this->customerOrderItems as $item) {
            $count += $item->getQuantity();
        }

        return $count;
    }

    public function isCancelled(): bool
    {
        return OrderStatus::CANCELLED === $this->status;
    }

    private function changeShippingPrice(string $shippingPrice): void
    {
        if ((float) $shippingPrice < 0) {
            throw new \InvalidArgumentException('The shipping price must be greater than 0');
        }

        $this->shippingPrice = $shippingPrice;
    }

    private function changeShippingPriceIncVat(string $shippingPriceIncVat): void
    {
        if ((float) $shippingPriceIncVat < 0) {
            throw new \InvalidArgumentException('The shipping price inc VAT must be greater than 0');
        }

        $this->shippingPriceIncVat = $shippingPriceIncVat;
    }

    private function changeStatus(OrderStatus $newStatus): void
    {
        $statusChange = StatusChange::from($this->getStatus(), $newStatus);

        if ($statusChange->hasChanged()) {
            $this->status = $newStatus;

            $this->raiseDomainEvent(
                new OrderStatusWasChangedEvent($this->getPublicId(), $statusChange)
            );
        }
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

    /**
     * @return Collection<int, CustomerOrderItem>
     */
    public function getCustomerOrderItems(): Collection
    {
        return $this->customerOrderItems;
    }

    public function addCustomerOrderItem(CustomerOrderItem $customerOrderItem): static
    {
        if (!$this->allowEdit()) {
            throw new \LogicException('Cannot add items to an order with this status');
        }

        if (!$this->customerOrderItems->contains($customerOrderItem)) {
            $this->customerOrderItems->add($customerOrderItem);
            $customerOrderItem->assignCustomerOrder($this);
        }

        $this->recalculateTotal();

        return $this;
    }

    public function removeCustomerOrderItem(CustomerOrderItem $customerOrderItem): static
    {
        if (!$this->allowEdit()) {
            throw new \LogicException('Cannot remove items from an order with this status');
        }

        // set the owning side to null (unless already changed)
        if (
            $this->customerOrderItems->removeElement($customerOrderItem)
            && $customerOrderItem->getCustomerOrder() === $this
        ) {
        }

        $this->recalculateTotal();

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
            $purchaseOrder->assignCustomerOrder($this);
        }

        return $this;
    }

    public function removePurchaseOrder(PurchaseOrder $purchaseOrder): static
    {
        // set the owning side to null (unless already changed)
        if ($this->purchaseOrders->removeElement($purchaseOrder) && $purchaseOrder->getCustomerOrder() === $this) {
            $purchaseOrder->assignCustomerOrder(null);
        }

        return $this;
    }
}
