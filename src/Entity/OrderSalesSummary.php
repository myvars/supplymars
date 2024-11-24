<?php

namespace App\Entity;

use App\Enum\SalesDuration;
use App\Repository\OrderSalesSummaryRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderSalesSummaryRepository::class)]
class OrderSalesSummary
{
    private function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 50)]
        private readonly SalesDuration $duration,
        #[ORM\Id]
        #[ORM\Column(length: 10)]
        private readonly string $dateString,
        #[ORM\Column]
        private readonly int $orderCount,
        #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
        private readonly string $orderValue,
        #[ORM\Column(type: Types::DATE_IMMUTABLE)]
        private readonly \DateTimeImmutable $salesDate
    ) {
    }

    public static function create(
        SalesDuration $duration,
        string $dateString,
        int $orderCount,
        string $orderValue,
    ): self{
        return new self(
            $duration,
            $dateString,
            $orderCount,
            $orderValue,
            new DateTimeImmutable($dateString)
        );
    }


    public function getDuration(): ?SalesDuration
    {
        return $this->duration;
    }

    public function getDateString(): ?string
    {
        return $this->dateString;
    }

    public function getOrderCount(): ?int
    {
        return $this->orderCount;
    }

    public function getOrderValue(): ?string
    {
        return $this->orderValue;
    }

    public function getSalesDate(): ?DateTimeImmutable
    {
        return $this->salesDate;
    }
}
