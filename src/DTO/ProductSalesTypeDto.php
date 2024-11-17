<?php

namespace App\DTO;

use App\Enum\Duration;
use App\Enum\SalesType;

class ProductSalesTypeDto
{
    private function __construct(
        private readonly string $salesType,
        private readonly string $duration,
        private readonly string $startDate,
        private readonly string $endDate,
        private readonly string $dateString,
        private readonly ?string $rangeStartDate
    ) {
    }

    public static function create(SalesType $salesType, Duration $duration, bool $rebuildRange): self
    {
        if ($duration->getStartDate($rebuildRange) > $duration->getEndDate()) {
            throw new \InvalidArgumentException('Start date must be less than end date');
        }

        return new self(
            $salesType->value,
            $duration->value,
            $duration->getStartDate($rebuildRange),
            $duration->getEndDate(),
            $duration->getDateStringFormat($rebuildRange),
            $duration->getRangeStartDate($rebuildRange)
        );
    }

    public function getSalesType(): string
    {
        return $this->salesType;
    }

    public function getDuration(): string
    {
        return $this->duration;
    }

    public function getStartDate(): string
    {
        return $this->startDate;
    }

    public function getEndDate(): string
    {
        return $this->endDate;
    }

    public function getDateString(): string
    {
        return $this->dateString;
    }

    public function getRangeStartDate(): ?string
    {
        return $this->rangeStartDate;
    }
}
