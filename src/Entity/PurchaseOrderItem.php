<?php

namespace App\Entity;

use App\Enum\PurchaseOrderStatus;
use App\Repository\PurchaseOrderItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PurchaseOrderItemRepository::class)]
class PurchaseOrderItem
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'purchaseOrderItems')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Please enter a purchase order')]
    private ?PurchaseOrder $purchaseOrder = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?SupplierProduct $supplierProduct = null;

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
    private PurchaseOrderStatus $status;

    #[ORM\ManyToOne(inversedBy: 'purchaseOrderItems')]
    private ?CustomerOrderItem $customerOrderItem = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $totalPrice = '0';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $totalPriceIncVat = '0';

    #[ORM\Column]
    private int $totalWeight = 0;

    public function __construct()
    {
        $this->status = PurchaseOrderStatus::getDefault();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPurchaseOrder(): ?PurchaseOrder
    {
        return $this->purchaseOrder;
    }

    public function setPurchaseOrder(?PurchaseOrder $purchaseOrder): static
    {
        $this->purchaseOrder = $purchaseOrder;

        return $this;
    }

    public function getSupplierProduct(): ?SupplierProduct
    {
        return $this->supplierProduct;
    }

    private function setSupplierProduct(?SupplierProduct $supplierProduct): static
    {
        $this->supplierProduct = $supplierProduct;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getMaxQuantity(): int
    {
        return $this->getCustomerOrderItem()->getOutstandingQty() + $this->getQuantity();
    }

    private function setQuantity(int $quantity): static
    {
        if ($quantity > $this->getMaxQuantity()) {
            throw new \InvalidArgumentException('Quantity cannot be greater than %s', $this->getMaxQuantity());
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
        $this->price = $price;

        return $this;
    }

    public function getPriceIncVat(): ?string
    {
        return $this->priceIncVat;
    }

    private function setPriceIncVat(string $priceIncVat): static
    {
        $this->priceIncVat = $priceIncVat;

        return $this;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    private function setWeight(int $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function getStatus(): PurchaseOrderStatus
    {
        return $this->status;
    }

    public function getCustomerOrderItem(): ?CustomerOrderItem
    {
        return $this->customerOrderItem;
    }

    public function setCustomerOrderItem(?CustomerOrderItem $customerOrderItem): static
    {
        $this->customerOrderItem = $customerOrderItem;

        return $this;
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

    public function recalculateTotal(): static
    {
        $this->totalPrice = bcmul((string) $this->quantity, $this->price, 2);
        $this->totalPriceIncVat = bcmul((string) $this->quantity, $this->priceIncVat, 2);
        $this->totalWeight = bcmul((string)$this->quantity, (string) $this->weight, 3);

        return $this;
    }

    public function allowEdit(): bool
    {
        return $this->status->allowEdit();
    }

    public function allowStatusChange(): bool
    {
        return $this->status !== PurchaseOrderStatus::DELIVERED && $this->status !== PurchaseOrderStatus::CANCELLED;
    }

    public function isCancelled(): bool
    {
        return $this->status->isCancelled();
    }

    public static function createFromCustomerOrderItem(
        CustomerOrderItem $customerOrderItem,
        PurchaseOrder $purchaseOrder,
        SupplierProduct $supplierProduct,
        int $quantity
    ): static {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than 0');
        }

        return (new static())
            ->setPurchaseOrder($purchaseOrder)
            ->setSupplierProduct($supplierProduct)
            ->setCustomerOrderItem($customerOrderItem)
            ->setPrice($supplierProduct->getCost())
            ->setPriceIncVat($supplierProduct->getCost())
            ->setWeight($supplierProduct->getWeight())
            ->setQuantity($quantity)
            ->recalculateTotal();
    }

    public function updateItem(int $quantity): static
    {
        return $this
            ->setQuantity($quantity)
            ->recalculateTotal();
    }

    public function updateStatus(PurchaseOrderStatus $newStatus): void
    {
        if ($newStatus === $this->status) {
            return;
        }

        if (!$this->status->canTransitionTo($newStatus)) {
            throw new \LogicException(sprintf('Cannot transition from "%s" to "%s"',
                $this->status->value,
                $newStatus->value
            ));
        }

        $this->status = $newStatus;
        $this->getPurchaseOrder()->generateStatus();
    }
}
