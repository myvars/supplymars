<?php

namespace App\Entity;

use App\Repository\ProductSalesSummaryRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductSalesSummaryRepository::class)]
class ProductSalesSummary
{
    private function __construct(
        #[ORM\Id]
        #[ORM\Column]
        private readonly int $salesId,
        #[ORM\Id]
        #[ORM\Column(length: 50)]
        private readonly string $salesType,
        #[ORM\Id]
        #[ORM\Column(length: 10)]
        private readonly string $duration,
        #[ORM\Id]
        #[ORM\Column(length: 10)]
        private readonly string $dateString,
        #[ORM\Column]
        private readonly int $salesQty,
        #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
        private readonly string $salesCost,
        #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
        private readonly string $salesValue,
        #[ORM\Column(type: Types::DATE_IMMUTABLE)]
        private readonly \DateTimeImmutable $salesDate
    ) {
    }

    public static function create(
        int $salesId,
        string $salesType,
        string $duration,
        string $dateString,
        int $salesQty,
        string $salesCost,
        string $salesValue,
    ): self{
        return new self($salesId, $salesType, $duration, $dateString, $salesQty, $salesCost, $salesValue, new DateTimeImmutable($dateString));
    }

    public function getSalesId(): ?int
    {
        return $this->salesId;
    }

    public function getSalesType(): ?string
    {
        return $this->salesType;
    }

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function getDateString(): ?string
    {
        return $this->dateString;
    }

    public function getSalesQty(): ?int
    {
        return $this->salesQty;
    }

    public function getSalesCost(): ?string
    {
        return $this->salesCost;
    }

    public function getSalesValue(): ?string
    {
        return $this->salesValue;
    }

    public function getSalesDate(): ?DateTimeImmutable
    {
        return $this->salesDate;
    }
}
