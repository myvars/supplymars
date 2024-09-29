<?php

namespace App\Entity;

use App\Enum\DomainEventType;
use App\Repository\SupplierStockChangeLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SupplierStockChangeLogRepository::class)]
class SupplierStockChangeLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Please enter a supplier product Id')]
    #[Assert\Positive(message: 'Please enter a positive supplier product Id')]
    private readonly int $supplierProductId;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Please enter a stock level')]
    #[Assert\Range(notInRangeMessage: 'Please enter a stock level', min: 0, max: 10000)]
    private readonly int $stock;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Please enter a cost')]
    #[Assert\PositiveOrZero]
    private readonly string $cost;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        #[ORM\Column(length: 255)]
        #[Assert\NotBlank(message: 'Please enter an event type')]
        private readonly DomainEventType $eventType,

        SupplierProduct $supplierProduct,

        #[ORM\Column]
        #[Assert\NotBlank(message: 'Please enter an event timestamp')]
        private readonly \DateTimeImmutable $eventTimestamp
    ) {
        $this->createdAt = new \DateTimeImmutable();
        $this->supplierProductId = $supplierProduct->getId();
        $this->stock = $supplierProduct->getStock();
        $this->cost = $supplierProduct->getCost();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSupplierProductId(): int
    {
        return $this->supplierProductId;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function getCost(): string
    {
        return $this->cost;
    }

    public function getEventType(): DomainEventType
    {
        return $this->eventType;
    }

    public function getEventTimestamp(): \DateTimeImmutable
    {
        return $this->eventTimestamp;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
