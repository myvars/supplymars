<?php

namespace App\Reporting\Domain\Model\SalesType;

use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Metric\SalesType;
use App\Reporting\Infrastructure\Persistence\Doctrine\ProductSalesSummaryDoctrineRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductSalesSummaryDoctrineRepository::class)]
class ProductSalesSummary
{
    final private function __construct(
        #[ORM\Id]
        #[ORM\Column]
        private readonly int $salesId,

        #[ORM\Id]
        #[ORM\Column(length: 50)]
        private readonly SalesType $salesType,

        #[ORM\Id]
        #[ORM\Column(length: 50)]
        private readonly SalesDuration $duration,

        #[ORM\Id]
        #[ORM\Column(length: 10)]
        #[Assert\NotBlank(message: 'Date string must not be blank')]
        #[Assert\Length(max: 10)]
        private readonly string $dateString,

        #[ORM\Column]
        #[Assert\PositiveOrZero(message: 'Sales quantity must be zero or positive')]
        private readonly int $salesQty,

        #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
        #[Assert\NotBlank(message: 'Sales cost must not be blank')]
        #[Assert\PositiveOrZero(message: 'Sales cost must be zero or positive')]
        private readonly string $salesCost,

        #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
        #[Assert\NotBlank(message: 'Sales value must not be blank')]
        #[Assert\PositiveOrZero(message: 'Sales value must be zero or positive')]
        private readonly string $salesValue,

        #[ORM\Column(type: Types::DATE_IMMUTABLE)]
        #[Assert\NotNull(message: 'Sales date must not be null')]
        private readonly \DateTimeImmutable $salesDate,
    ) {
    }

    public static function create(
        ProductSalesType $productSalesType,
        int $salesId,
        string $dateString,
        int $salesQty,
        string $salesCost,
        string $salesValue,
    ): self {
        return new self(
            $salesId,
            $productSalesType->getSalesType(),
            $productSalesType->getDuration(),
            $dateString,
            $salesQty,
            $salesCost,
            $salesValue,
            new \DateTimeImmutable($dateString)
        );
    }

    public function getSalesId(): ?int
    {
        return $this->salesId;
    }

    public function getSalesType(): ?SalesType
    {
        return $this->salesType;
    }

    public function getDuration(): ?SalesDuration
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

    public function getSalesDate(): ?\DateTimeImmutable
    {
        return $this->salesDate;
    }
}
