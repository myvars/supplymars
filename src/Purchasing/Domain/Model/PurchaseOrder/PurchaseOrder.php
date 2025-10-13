<?php

namespace App\Purchasing\Domain\Model\PurchaseOrder;

use App\Customer\Domain\Model\Address\Address;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\Event\PurchaseOrderStatusWasChangedEvent;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Infrastructure\Persistence\Doctrine\PurchaseOrderDoctrineRepository;
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

#[ORM\Entity(repositoryClass: PurchaseOrderDoctrineRepository::class)]
class PurchaseOrder implements DomainEventProviderInterface
{
    use TimestampableEntity;
    use DomainEventProviderTrait;
    use HasPublicUlid;

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
        $this->initializePublicId();
        $this->status = PurchaseOrderStatus::getDefault();
        $this->purchaseOrderItems = new ArrayCollection();
    }

    public static function createFromOrder(CustomerOrder $customerOrder, Supplier $supplier): static
    {
        $self = new self();
        $self->customerOrder = $customerOrder;
        $self->shippingAddress = $customerOrder->getShippingAddress();
        $self->shippingMethod = $customerOrder->getShippingMethod();
        $self->dueDate = $customerOrder->getShippingMethod()->getDueDate();
        $self->changeShippingPrice($customerOrder->getShippingMethod()->getPrice());
        $self->changeShippingPriceIncVat($customerOrder->getShippingMethod()->getPrice());
        $self->orderRef = $customerOrder->getCustomerOrderRef();
        $self->supplier = $supplier;

        $self->getCustomerOrder()->addPurchaseOrder($self);
        $self->recalculateTotal();

        return $self;
    }

    public function assignCustomerOrder(?CustomerOrder $customerOrder): void
    {
        $this->customerOrder = $customerOrder;
    }

    public function assignSupplier(Supplier $supplier): void
    {
        $this->supplier = $supplier;
    }

    public function assignShippingAddress(Address $shippingAddress): void
    {
        $this->shippingAddress = $shippingAddress;
    }

    public function allowEdit(): bool
    {
        return $this->status->allowEdit();
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

        $this->changeTotalPrice((string) $totalPrice);
        $this->changeTotalPriceIncVat((string) $totalPriceIncVat);
        $this->changeTotalWeight($totalWeight);
    }

    public function generateStatus(): void
    {
        if ($this->purchaseOrderItems->isEmpty()) {
            $this->changeStatus(PurchaseOrderStatus::getDefault());

            return;
        }

        $status = null;
        foreach ($this->purchaseOrderItems as $item) {
            if (null === $status || $item->getStatus()->getLevel() < $status->getLevel()) {
                $status = $item->getStatus();
            }
        }

        if ($this->getStatus() === $status) {
            return;
        }

        if (!$this->getStatus()->canTransitionTo($status)) {
            throw new \LogicException(sprintf('Cannot transition from "%s" to "%s"', $this->getStatus()->value, $status->value));
        }

        $this->changeStatus($status);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublicId(): PurchaseOrderPublicId
    {
        return PurchaseOrderPublicId::fromString($this->publicIdString());
    }

    public function getCustomerOrder(): ?CustomerOrder
    {
        return $this->customerOrder;
    }

    public function getSupplier(): Supplier
    {
        return $this->supplier;
    }

    public function getShippingAddress(): Address
    {
        return $this->shippingAddress;
    }

    public function getOrderRef(): ?string
    {
        return $this->orderRef;
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

    public function getStatus(): PurchaseOrderStatus
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

    private function changeShippingPrice(string $shippingPrice): void
    {
        if ((float) $shippingPrice < 0) {
            throw new \InvalidArgumentException('Shipping price cannot be negative');
        }
        $this->shippingPrice = $shippingPrice;
    }

    private function changeShippingPriceIncVat(string $shippingPriceIncVat): void
    {
        if ((float) $shippingPriceIncVat < 0) {
            throw new \InvalidArgumentException('Shipping price inc VAT cannot be negative');
        }
        $this->shippingPriceIncVat = $shippingPriceIncVat;
    }

    private function changeStatus(PurchaseOrderStatus $newStatus): void
    {
        $statusChange = StatusChange::from($this->getStatus(), $newStatus);

        if ($statusChange->hasChanged()) {
            $this->status = $newStatus;

            $this->raiseDomainEvent(new PurchaseOrderStatusWasChangedEvent($this->getPublicId(), $statusChange));
        }
    }

    private function changeTotalPrice(string $totalPrice): void
    {
        if ((float) $totalPrice < 0) {
            throw new \InvalidArgumentException('Total price cannot be negative');
        }
        $this->totalPrice = $totalPrice;
    }

    private function changeTotalPriceIncVat(string $totalPriceIncVat): void
    {
        if ((float) $totalPriceIncVat < 0) {
            throw new \InvalidArgumentException('Total price inc VAT cannot be negative');
        }
        $this->totalPriceIncVat = $totalPriceIncVat;
    }

    private function changeTotalWeight(int $totalWeight): void
    {
        if ($totalWeight < 0) {
            throw new \InvalidArgumentException('Total weight cannot be negative');
        }
        $this->totalWeight = $totalWeight;
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
            $purchaseOrderItem->assignPurchaseOrder($this);
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
}
