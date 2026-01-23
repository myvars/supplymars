<?php

namespace App\Audit\Domain\Model\StockChange;

use App\Audit\Infrastructure\Persistence\Doctrine\SupplierStockChangeLogDoctrineRepository;
use App\Shared\Domain\Event\DomainEventType;
use App\Shared\Domain\ValueObject\CostChange;
use App\Shared\Domain\ValueObject\StockChange;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SupplierStockChangeLogDoctrineRepository::class)]
#[ORM\Index(columns: ['supplier_product_id', 'event_type'])]
class SupplierStockChangeLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    final private function __construct(
        #[ORM\Column(length: 255)]
        #[Assert\Choice(choices: [
            DomainEventType::SUPPLIER_PRODUCT_STOCK_CHANGED,
            DomainEventType::SUPPLIER_PRODUCT_COST_CHANGED,
        ], message: 'Invalid event type')]
        private readonly DomainEventType $eventType,

        #[ORM\Column]
        private readonly int $supplierProductId,

        #[ORM\Column]
        #[Assert\Range(notInRangeMessage: 'Please enter a stock level', min: 0, max: 10000)]
        private readonly int $stock,

        #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
        #[Assert\PositiveOrZero(message: 'Please enter a positive or zero cost')]
        private readonly string $cost,

        #[ORM\Column]
        #[Assert\NotNull(message: 'Event timestamp must not be null')]
        private readonly \DateTimeImmutable $eventTimestamp,
    ) {
    }

    public static function create(
        DomainEventType $type,
        int $supplierProductId,
        StockChange $stockChange,
        CostChange $costChange,
        \DateTimeImmutable $occurredAt,
    ): self {
        return new self(
            $type,
            $supplierProductId,
            $stockChange->after(),
            $costChange->after(),
            $occurredAt
        );
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
}
