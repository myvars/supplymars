<?php

namespace App\Entity;

use App\Enum\OrderStatus;
use App\Enum\ShippingMethod;
use App\Repository\CustomerOrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CustomerOrderRepository::class)]
class CustomerOrder
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'customerOrders')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Please enter a customer')]
    private User $customer;

    #[ORM\ManyToOne(inversedBy: 'customerOrders')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Please enter a shipping address')]
    private Address $shippingAddress;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Please enter a billing address')]
    private Address $billingAddress;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $CustomerOrderRef = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter a shipping method')]
    private ShippingMethod $shippingMethod;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Please enter a due date')]
    private \DateTimeImmutable $dueDate;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Please enter a shipping price')]
    #[Assert\Range(notInRangeMessage: 'Please enter a shipping price (0 to 100000)', min: 0, max: 100000)]
    private string $shippingPrice = '0';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Please enter a shipping price inc VAT')]
    #[Assert\Range(notInRangeMessage: 'Please enter a shipping price inc VAT (0 to 100000)', min: 0, max: 100000)]
    private string $shippingPriceIncVat = '0';

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter a status')]
    private ?OrderStatus $status;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $totalPrice = '0';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $totalPriceIncVat = '0';

    #[ORM\Column]
    private int $totalWeight = 0;

    /**
     * @var Collection<int, CustomerOrderItem>
     */
    #[ORM\OneToMany(mappedBy: 'customerOrder', targetEntity: CustomerOrderItem::class)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $customerOrderItems;

    /**
     * @var Collection<int, PurchaseOrder>
     */
    #[ORM\OneToMany(mappedBy: 'customerOrder', targetEntity: PurchaseOrder::class)]
    private Collection $purchaseOrders;

    public function __construct()
    {
        $this->status = OrderStatus::getDefault();
        $this->customerOrderItems = new ArrayCollection();
        $this->purchaseOrders = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function setBillingAddress(Address $billingAddress): static
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    public function getCustomerOrderRef(): ?string
    {
        return $this->CustomerOrderRef;
    }

    public function setCustomerOrderRef(?string $CustomerOrderRef): static
    {
        $this->CustomerOrderRef = $CustomerOrderRef;

        return $this;
    }

    public function getShippingMethod(): ShippingMethod
    {
        return $this->shippingMethod;
    }

    public function setShippingMethod(ShippingMethod $shippingMethod): static
    {
        $this->shippingMethod = $shippingMethod;

        return $this;
    }

    public function getDueDate(): \DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function setDueDate(\DateTimeImmutable $dueDate): static
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    public function getShippingPrice(): string
    {
        return $this->shippingPrice;
    }

    public function setShippingPrice(string $shippingPrice): static
    {
        $this->shippingPrice = $shippingPrice;

        return $this;
    }

    public function getShippingPriceIncVat(): string
    {
        return $this->shippingPriceIncVat;
    }

    public function setShippingPriceIncVat(string $shippingPriceIncVat): static
    {
        $this->shippingPriceIncVat = $shippingPriceIncVat;

        return $this;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function setStatus(OrderStatus $status): static
    {
        $this->status = $status;

        return $this;
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

        if ($this->customerOrderItems->removeElement($customerOrderItem)) {
            // set the owning side to null (unless already changed)
            if ($customerOrderItem->getCustomerOrder() === $this) {
                $customerOrderItem->setCustomerOrder(null);
            }
        }
        $this->recalculateTotal();

        return $this;
    }

    public function setShippingDetailsFromShippingMethod(ShippingMethod $shippingMethod, VatRate $vatRate): static
    {
        $this->setShippingMethod($shippingMethod);
        $this->setShippingPrice($shippingMethod->getPrice());
        $this->setShippingPriceIncVat($shippingMethod->getPriceIncVat($vatRate));
        $this->setDueDate($shippingMethod->getDueDate());
        $this->recalculateTotal();

        return $this;
    }

    public function recalculateTotal(): void
    {
        $totalPrice = 0;
        $totalPriceIncVat = 0;
        $totalWeight = 0;

        foreach ($this->customerOrderItems as $customerOrderItem) {
            $totalPrice += $customerOrderItem->getTotalPrice();
            $totalPriceIncVat += $customerOrderItem->getTotalPriceIncVat();
            $totalWeight += $customerOrderItem->getTotalWeight();
        }

        $totalPrice += (float) $this->shippingPrice;
        $totalPriceIncVat += (float) $this->shippingPriceIncVat;

        $this->totalPrice = (string) $totalPrice;
        $this->totalPriceIncVat = (string) $totalPriceIncVat;
        $this->totalWeight = $totalWeight;
    }

    public function allowEdit(): bool
    {
        return $this->status->allowEdit();
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
        if ($this->purchaseOrders->removeElement($purchaseOrder)) {
            // set the owning side to null (unless already changed)
            if ($purchaseOrder->getCustomerOrder() === $this) {
                $purchaseOrder->setCustomerOrder(null);
            }
        }

        return $this;
    }
}