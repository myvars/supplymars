<?php

namespace App\ValueObject;

use App\Enum\SalesDuration;
use App\Enum\SalesType;

class ProductSalesType
{
    private function __construct(
        private readonly SalesType $salesType,
        private readonly SalesDuration $duration,
        private readonly bool $rebuildRange
    ) {
    }

    public static function create(SalesType $salesType, SalesDuration $duration, bool $rebuildRange = false): self
    {
        if ($duration->getStartDate($rebuildRange) > $duration->getEndDate()) {
            throw new \InvalidArgumentException('Start date must be less than end date');
        }

        return new self($salesType, $duration, $rebuildRange);
    }

    public function getSalesType(): SalesType
    {
        return $this->salesType;
    }

    public function getDuration(): SalesDuration
    {
        return $this->duration;
    }

    public function getStartDate(): string
    {
        return $this->duration->getStartDate($this->rebuildRange);
    }

    public function getEndDate(): string
    {
        return $this->duration->getEndDate();
    }

    public function getDateString(): string
    {
        return $this->duration->getDateStringFormat($this->rebuildRange);
    }

    public function getRangeStartDate(): ?string
    {
        return $this->duration->getRangeStartDate($this->rebuildRange);
    }
}
