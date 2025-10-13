<?php

namespace App\Audit\Infrastructure\Logging;

use App\Audit\Domain\Model\StockChange\SupplierStockChangeLog;
use App\Audit\Domain\Repository\SupplierStockChangeLogRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Domain\Event\DomainEventType;
use App\Shared\Domain\ValueObject\CostChange;
use App\Shared\Domain\ValueObject\StockChange;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class SupplierStockChangeLogWriter
{
    public function __construct(
        private SupplierStockChangeLogRepository $supplierStockChangeLogs,
        private ValidatorInterface $validator,
        private FlusherInterface $flusher,
    ) {
    }

    public function write(
        DomainEventType $type,
        int $supplierProductId,
        StockChange $stockChange,
        CostChange $costChange,
        \DateTimeImmutable $occurredAt,
    ): void {
        $supplierStockChangeLog = SupplierStockChangeLog::create(
            $type,
            $supplierProductId,
            $stockChange,
            $costChange,
            $occurredAt,
        );

        $errors = $this->validator->validate($supplierStockChangeLog);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors);
        }

        $this->supplierStockChangeLogs->add($supplierStockChangeLog);
        $this->flusher->flush();
    }
}
