<?php

namespace App\Reporting\Domain\Model\SalesType;

use App\Reporting\Domain\Metric\SalesDuration;

final readonly class CustomerSalesType
{
    private function __construct(
        private SalesDuration $duration,
        private bool $rebuildRange,
    ) {
    }

    public static function create(SalesDuration $duration, bool $rebuildRange = false): self
    {
        if ($duration->getStartDate($rebuildRange) > $duration->getEndDate()) {
            throw new \InvalidArgumentException('Start date must be less than end date');
        }

        return new self($duration, $rebuildRange);
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
