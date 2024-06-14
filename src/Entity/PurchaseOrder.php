<?php

namespace App\Entity;

use App\Enum\PurchaseOrderStatus;
use App\Enum\ShippingMethod;
use App\Repository\PurchaseOrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PurchaseOrderRepository::class)]
class PurchaseOrder
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'purchaseOrders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CustomerOrder $customerOrder = null;

    #[ORM\ManyToOne(inversedBy: 'purchaseOrders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Supplier $supplier = null;

    #[ORM\ManyToOne(inversedBy: 'purchaseOrders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Address $shippingAddress = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $orderRef = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?ShippingMethod $shippingMethod = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dueDate = null;

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
    private PurchaseOrderStatus $status;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $totalPrice = '0';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $totalPriceIncVat = '0';

    #[ORM\Column]
    private int $totalWeight = 0;

    /**
     * @var Collection<int, PurchaseOrderItem>
     */
    #[ORM\OneToMany(mappedBy: 'purchaseOrder', targetEntity: PurchaseOrderItem::class)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $purchaseOrderItems;

    public function __construct()
    {
        $this->status = PurchaseOrderStatus::getDefault();
        $this->purchaseOrderItems = new ArrayCollection();
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

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(?Supplier $supplier): static
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function getShippingAddress(): ?Address
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?Address $shippingAddress): static
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

    public function getShippingMethod(): ?ShippingMethod
    {
        return $this->shippingMethod;
    }

    private function setShippingMethod(?ShippingMethod $shippingMethod): static
    {
        $this->shippingMethod = $shippingMethod;

        return $this;
    }

    public function getDueDate(): ?\DateTimeImmutable
    {
        return $this->dueDate;
    }

    private function setDueDate(?\DateTimeImmutable $dueDate): static
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
        if ($this->purchaseOrderItems->removeElement($purchaseOrderItem)) {
            // set the owning side to null (unless already changed)
            if ($purchaseOrderItem->getPurchaseOrder() === $this) {
                $purchaseOrderItem->setPurchaseOrder(null);
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

    public function recalculateTotal(): static
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

        $this->totalPrice = (string) $totalPrice;
        $this->totalPriceIncVat = (string) $totalPriceIncVat;
        $this->totalWeight = $totalWeight;

        return $this;
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

    public static function createFromOrder(
        CustomerOrder $customerOrder,
        Supplier $supplier,
    ): static {
        return (new static())
            ->setCustomerOrder($customerOrder)
            ->setSupplier($supplier)
            ->setShippingAddress($customerOrder->getShippingAddress())
            ->setShippingMethod($customerOrder->getShippingMethod())
            ->setDueDate($customerOrder->getShippingMethod()->getDueDate())
            ->setShippingPrice($customerOrder->getShippingMethod()->getPrice())
            ->setShippingPriceIncVat($customerOrder->getShippingMethod()->getPrice())
            ->setOrderRef($customerOrder->getCustomerOrderRef())
            ->recalculateTotal();
    }

    public function generateStatus(): void
    {
        if ($this->purchaseOrderItems->isEmpty()) {
            $this->setStatus(PurchaseOrderStatus::getDefault());
            return;
        }

        $status = PurchaseOrderStatus::CANCELLED;
        foreach ($this->purchaseOrderItems as $item) {
            if ($item->getStatus()->getLevel() < $status->getLevel()) {
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
}