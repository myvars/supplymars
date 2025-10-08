<?php

namespace App\Entity;

use App\Enum\OrderStatus;
use App\Enum\ShippingMethod;
use App\Event\OrderStatusWasChangedEvent;
use App\Repository\CustomerOrderRepository;
use App\ValueObject\CustomerOrderPublicId;
use App\ValueObject\StatusChange;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CustomerOrderRepository::class)]
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

    private function __construct()
    {
        $this->initializePublicId();
        $this->status = OrderStatus::getDefault();
        $this->customerOrderItems = new ArrayCollection();
        $this->purchaseOrders = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublicId(): CustomerOrderPublicId
    {
        return CustomerOrderPublicId::fromString($this->publicIdString());
    }

    public function getCustomer(): User
    {
        return $this->customer;
    }

    public function setCustomer(User $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getShippingAddress(): Address
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(Address $shippingAddress): static
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
    }

    public function getBillingAddress(): Address
    {
        return $this->billingAddress;
    }

    private function setBillingAddress(Address $billingAddress): static
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    public function getCustomerOrderRef(): ?string
    {
        return $this->customerOrderRef;
    }

    private function setCustomerOrderRef(?string $customerOrderRef): static
    {
        $this->customerOrderRef = $customerOrderRef;

        return $this;
    }

    public function getShippingMethod(): ShippingMethod
    {
        return $this->shippingMethod;
    }

    private function setShippingMethod(ShippingMethod $shippingMethod): static
    {
        $this->shippingMethod = $shippingMethod;

        return $this;
    }

    public function getDueDate(): \DateTimeImmutable
    {
        return $this->dueDate;
    }

    private function setDueDate(\DateTimeImmutable $dueDate): static
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    public function getShippingPrice(): string
    {
        return $this->shippingPrice;
    }

    private function setShippingPrice(string $shippingPrice): static
    {
        if ((float) $shippingPrice < 0) {
            throw new \InvalidArgumentException('The shipping price must be greater than 0');
        }

        $this->shippingPrice = $shippingPrice;

        return $this;
    }

    public function getShippingPriceIncVat(): string
    {
        return $this->shippingPriceIncVat;
    }

    private function setShippingPriceIncVat(string $shippingPriceIncVat): static
    {
        if ((float) $shippingPriceIncVat < 0) {
            throw new \InvalidArgumentException('The shipping price inc VAT must be greater than 0');
        }

        $this->shippingPriceIncVat = $shippingPriceIncVat;

        return $this;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    private function setStatus(OrderStatus $newStatus): static
    {
        $statusChange = StatusChange::from($this->getStatus(), $newStatus);

        if ($statusChange->hasChanged()) {
            $this->status = $newStatus;

            $this->raiseDomainEvent(new OrderStatusWasChangedEvent($this->getPublicId(), $statusChange));
        }

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

    public function getOrderLock(): ?User
    {
        return $this->orderLock;
    }

    private function setOrderLock(?User $orderLock): static
    {
        $this->orderLock = $orderLock;

        return $this;
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
            $customerOrderItem->setCustomerOrder($this);
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
            $purchaseOrder->setCustomerOrder($this);
        }

        return $this;
    }

    public function removePurchaseOrder(PurchaseOrder $purchaseOrder): static
    {
        // set the owning side to null (unless already changed)
        if ($this->purchaseOrders->removeElement($purchaseOrder) && $purchaseOrder->getCustomerOrder() === $this) {
            $purchaseOrder->setCustomerOrder(null);
        }

        return $this;
    }

    public static function createFromCustomer(
        User $customer,
        ShippingMethod $shippingMethod,
        VatRate $vatRate,
        ?string $customerOrderRef,
    ): static {
        $customerOrder = new static()
            ->setCustomer($customer)
            ->setShippingAddress($customer->getShippingAddress())
            ->setBillingAddress($customer->getBillingAddress())
            ->setCustomerOrderRef($customerOrderRef)
            ->setShippingMethod($shippingMethod)
            ->setShippingPrice($shippingMethod->getPrice())
            ->setShippingPriceIncVat($shippingMethod->getPriceIncVat($vatRate))
            ->setDueDate($shippingMethod->getDueDate());

        $customerOrder->recalculateTotal();

        return $customerOrder;
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

        $this->setTotalPrice((string) $totalPrice);
        $this->setTotalPriceIncVat((string) $totalPriceIncVat);
        $this->setTotalWeight($totalWeight);
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

        $this->setStatus($orderStatus);
        $this->recalculateTotal();
    }

    public function allowEdit(): bool
    {
        return $this->status->allowEdit();
    }

    public function allowCancel(): bool
    {
        return $this->status->allowCancel();
    }

    public function isCancelled(): bool
    {
        return OrderStatus::CANCELLED === $this->status;
    }

    public function cancelOrder(): void
    {
        if (!$this->allowCancel()) {
            throw new \LogicException('Cannot cancel an order with this status');
        }

        $this->setStatus(OrderStatus::CANCELLED);
    }

    public function lockOrder(?User $user): void
    {
        $this->setOrderLock($user);
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
}
