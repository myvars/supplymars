<?php

namespace App\Reporting\Domain\Model\SalesType;

use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Infrastructure\Persistence\Doctrine\CustomerGeographicSummaryDoctrineRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CustomerGeographicSummaryDoctrineRepository::class)]
class CustomerGeographicSummary
{
    final private function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 100)]
        #[Assert\NotBlank(message: 'City must not be blank')]
        private readonly string $city,

        #[ORM\Id]
        #[ORM\Column(length: 50)]
        #[Assert\NotBlank(message: 'Sales duration must not be blank')]
        private readonly SalesDuration $duration,

        #[ORM\Id]
        #[ORM\Column(length: 10)]
        #[Assert\NotBlank(message: 'Date string must not be blank')]
        #[Assert\Length(max: 10)]
        private readonly string $dateString,

        #[ORM\Column]
        #[Assert\PositiveOrZero(message: 'Customer count must be zero or positive')]
        private readonly int $customerCount,

        #[ORM\Column]
        #[Assert\PositiveOrZero(message: 'Order count must be zero or positive')]
        private readonly int $orderCount,

        #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 2)]
        #[Assert\NotBlank(message: 'Order value must not be blank')]
        #[Assert\PositiveOrZero(message: 'Order value must be zero or positive')]
        private readonly string $orderValue,

        #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 2)]
        #[Assert\NotBlank(message: 'Average order value must not be blank')]
        #[Assert\PositiveOrZero(message: 'Average order value must be zero or positive')]
        private readonly string $averageOrderValue,

        #[ORM\Column(type: Types::DATE_IMMUTABLE)]
        #[Assert\NotNull(message: 'Sales date must not be null')]
        private readonly \DateTimeImmutable $salesDate,
    ) {
    }

    public static function create(
        CustomerSalesType $customerSalesType,
        string $city,
        string $dateString,
        int $customerCount,
        int $orderCount,
        string $orderValue,
        string $averageOrderValue,
    ): self {
        return new self(
            $city,
            $customerSalesType->getDuration(),
            $dateString,
            $customerCount,
            $orderCount,
            $orderValue,
            $averageOrderValue,
            new \DateTimeImmutable($dateString),
        );
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getDuration(): SalesDuration
    {
        return $this->duration;
    }

    public function getDateString(): string
    {
        return $this->dateString;
    }

    public function getCustomerCount(): int
    {
        return $this->customerCount;
    }

    public function getOrderCount(): int
    {
        return $this->orderCount;
    }

    public function getOrderValue(): string
    {
        return $this->orderValue;
    }

    public function getAverageOrderValue(): string
    {
        return $this->averageOrderValue;
    }

    public function getSalesDate(): \DateTimeImmutable
    {
        return $this->salesDate;
    }
}
