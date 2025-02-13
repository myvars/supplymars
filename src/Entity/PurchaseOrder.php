<?php

namespace App\Entity;

use App\Enum\PurchaseOrderStatus;
use App\Enum\ShippingMethod;
use App\Event\PurchaseOrderCreatedEvent;
use App\Event\PurchaseOrderStatusChangedEvent;
use App\Repository\PurchaseOrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PurchaseOrderRepository::class)]
class PurchaseOrder implements DomainEventProviderInterface
{
    use TimestampableEntity;
    use DomainEventTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'purchaseOrders')]
    #[ORM\JoinColumn(nullable: false)]
    private CustomerOrder $customerOrder;

    #[ORM\ManyToOne(inversedBy: 'purchaseOrders')]
    #[ORM\JoinColumn(nullable: false)]
    private Supplier $supplier;

    #[ORM\ManyToOne(inversedBy: 'purchaseOrders')]
    #[ORM\JoinColumn(nullable: false)]
    private Address $shippingAddress;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $orderRef = null;

    #[ORM\Column(length: 255)]
    private ShippingMethod $shippingMethod;

    #[ORM\Column]
    private \DateTimeImmutable $dueDate;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $shippingPrice = '0';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $shippingPriceIncVat = '0';

    #[ORM\Column(length: 255)]
    private PurchaseOrderStatus $status;

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
    #[ORM\OneToMany(targetEntity: PurchaseOrderItem::class, mappedBy: 'purchaseOrder')]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $purchaseOrderItems;

    private function __construct()
    {
        $this->status = PurchaseOrderStatus::getDefault();
        $this->purchaseOrderItems = new ArrayCollection();
        $this->raiseDomainEvent(new PurchaseOrderCreatedEvent($this));
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

    public function getSupplier(): Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(Supplier $supplier): static
    {
        $this->supplier = $supplier;

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

    public function getOrderRef(): ?string
    {
        return $this->orderRef;
    }

    private function setOrderRef(?string $orderRef): static
    {
        $this->orderRef = $orderRef;

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
        $this->shippingPrice = $shippingPrice;

        return $this;
    }

    public function getShippingPriceIncVat(): string
    {
        return $this->shippingPriceIncVat;
    }

    private function setShippingPriceIncVat(string $shippingPriceIncVat): static
    {
        $this->shippingPriceIncVat = $shippingPriceIncVat;

        return $this;
    }

    public function getStatus(): PurchaseOrderStatus
    {
        return $this->status;
    }

    private function setStatus(PurchaseOrderStatus $status): void
    {
        if ($this->status === $status) {
            return;
        }

        $this->status = $status;
        $this->raiseDomainEvent(new PurchaseOrderStatusChangedEvent($this));
    }

    public function getTotalPrice(): string
    {
        return $this->totalPrice;
    }

    private function setTotalPrice(string $totalPrice): static
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    public function getTotalPriceIncVat(): string
    {
        return $this->totalPriceIncVat;
    }

    private function setTotalPriceIncVat(string $totalPriceIncVat): static
    {
        $this->totalPriceIncVat = $totalPriceIncVat;

        return $this;
    }

    public function getTotalWeight(): int
    {
        return $this->totalWeight;
    }

    private function setTotalWeight(int $totalWeight): static
    {
        $this->totalWeight = $totalWeight;

        return $this;
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
            $purchaseOrderItem->setPurchaseOrder($this);
        }

        $this->recalculateTotal();

        return $this;
    }

    public function removePurchaseOrderItem(PurchaseOrderItem $purchaseOrderItem): static
    {
        // set the owning side to null (unless already changed)
        if (
            $this->purchaseOrderItems->removeElement($purchaseOrderItem)
            && $purchaseOrderItem->getPurchaseOrder() === $this
        ) {

        }

        $this->recalculateTotal();

        return $this;
    }

    public static function createFromOrder(
        CustomerOrder $customerOrder,
        Supplier $supplier,
    ): static {
        $purchaseOrder =  (new static())
            ->setCustomerOrder($customerOrder)
            ->setShippingAddress($customerOrder->getShippingAddress())
            ->setShippingMethod($customerOrder->getShippingMethod())
            ->setDueDate($customerOrder->getShippingMethod()->getDueDate())
            ->setShippingPrice($customerOrder->getShippingMethod()->getPrice())
            ->setShippingPriceIncVat($customerOrder->getShippingMethod()->getPrice())
            ->setOrderRef($customerOrder->getCustomerOrderRef())
            ->setSupplier($supplier);

        $purchaseOrder->getCustomerOrder()->addPurchaseOrder($purchaseOrder);
        $purchaseOrder->recalculateTotal();

        return $purchaseOrder;
    }

    public function recalculateTotal(): void
    {
        $totalPrice = 0;
        $totalPriceIncVat = 0;
        $totalWeight = 0;

        foreach ($this->purchaseOrderItems as $purchaseOrderItem) {
            $totalPrice += $purchaseOrderItem->getTotalPrice();
            $totalPriceIncVat += $purchaseOrderItem->getTotalPriceIncVat();
            $totalWeight += $purchaseOrderItem->getTotalWeight();
        }

        $totalPrice += (float) $this->shippingPrice;
        $totalPriceIncVat += (float) $this->shippingPriceIncVat;

        $this->setTotalPrice((string) $totalPrice);
        $this->setTotalPriceIncVat((string) $totalPriceIncVat);
        $this->setTotalWeight($totalWeight);
    }

    public function generateStatus(): void
    {
        if ($this->purchaseOrderItems->isEmpty()) {
            $this->setStatus(PurchaseOrderStatus::getDefault());

            return;
        }

        $status = null;
        foreach ($this->purchaseOrderItems as $item) {
            if ($status === null || $item->getStatus()->getLevel() < $status->getLevel()) {
                $status = $item->getStatus();
            }
        }

        if ($this->getStatus() === $status) {
            return;
        }

        if (!$this->getStatus()->canTransitionTo($status)) {
            throw new \LogicException(sprintf('Cannot transition from "%s" to "%s"',
                $this->getStatus()->value,
                $status->value
            ));
        }

        $this->setStatus($status);
    }

    public function allowEdit(): bool
    {
        return $this->status->allowEdit();
    }

    public function getLineCount(): int
    {
        return $this->purchaseOrderItems->count();
    }

    public function getItemCount(): int
    {
        $count = 0;
        foreach ($this->purchaseOrderItems as $item) {
            $count += $item->getQuantity();
        }

        return $count;
    }
}