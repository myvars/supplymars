<?php

namespace App\Reporting\Domain\Model\SalesType;

use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Infrastructure\Persistence\Doctrine\CustomerSalesSummaryDoctrineRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CustomerSalesSummaryDoctrineRepository::class)]
class CustomerSalesSummary
{
    final private function __construct(
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
        #[Assert\PositiveOrZero(message: 'Total customers must be zero or positive')]
        private readonly int $totalCustomers,

        #[ORM\Column]
        #[Assert\PositiveOrZero(message: 'Active customers must be zero or positive')]
        private readonly int $activeCustomers,

        #[ORM\Column]
        #[Assert\PositiveOrZero(message: 'New customers must be zero or positive')]
        private readonly int $newCustomers,

        #[ORM\Column]
        #[Assert\PositiveOrZero(message: 'Returning customers must be zero or positive')]
        private readonly int $returningCustomers,

        #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 2)]
        #[Assert\NotBlank(message: 'Total revenue must not be blank')]
        #[Assert\PositiveOrZero(message: 'Total revenue must be zero or positive')]
        private readonly string $totalRevenue,

        #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 2)]
        #[Assert\NotBlank(message: 'Average CLV must not be blank')]
        #[Assert\PositiveOrZero(message: 'Average CLV must be zero or positive')]
        private readonly string $averageClv,

        #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 2)]
        #[Assert\NotBlank(message: 'Average AOV must not be blank')]
        #[Assert\PositiveOrZero(message: 'Average AOV must be zero or positive')]
        private readonly string $averageAov,

        #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
        #[Assert\NotBlank(message: 'Repeat rate must not be blank')]
        #[Assert\PositiveOrZero(message: 'Repeat rate must be zero or positive')]
        private readonly string $repeatRate,

        #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
        #[Assert\NotBlank(message: 'Review rate must not be blank')]
        #[Assert\PositiveOrZero(message: 'Review rate must be zero or positive')]
        private readonly string $reviewRate,

        #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
        #[Assert\NotBlank(message: 'Average orders per customer must not be blank')]
        #[Assert\PositiveOrZero(message: 'Average orders per customer must be zero or positive')]
        private readonly string $averageOrdersPerCustomer,

        #[ORM\Column(type: Types::DATE_IMMUTABLE)]
        #[Assert\NotNull(message: 'Sales date must not be null')]
        private readonly \DateTimeImmutable $salesDate,
    ) {
    }

    public static function create(
        CustomerSalesType $customerSalesType,
        string $dateString,
        int $totalCustomers,
        int $activeCustomers,
        int $newCustomers,
        int $returningCustomers,
        string $totalRevenue,
        string $averageClv,
        string $averageAov,
        string $repeatRate,
        string $reviewRate,
        string $averageOrdersPerCustomer,
    ): self {
        return new self(
            $customerSalesType->getDuration(),
            $dateString,
            $totalCustomers,
            $activeCustomers,
            $newCustomers,
            $returningCustomers,
            $totalRevenue,
            $averageClv,
            $averageAov,
            $repeatRate,
            $reviewRate,
            $averageOrdersPerCustomer,
            new \DateTimeImmutable($dateString),
        );
    }

    public function getDuration(): SalesDuration
    {
        return $this->duration;
    }

    public function getDateString(): string
    {
        return $this->dateString;
    }

    public function getTotalCustomers(): int
    {
        return $this->totalCustomers;
    }

    public function getActiveCustomers(): int
    {
        return $this->activeCustomers;
    }

    public function getNewCustomers(): int
    {
        return $this->newCustomers;
    }

    public function getReturningCustomers(): int
    {
        return $this->returningCustomers;
    }

    public function getTotalRevenue(): string
    {
        return $this->totalRevenue;
    }

    public function getAverageClv(): string
    {
        return $this->averageClv;
    }

    public function getAverageAov(): string
    {
        return $this->averageAov;
    }

    public function getRepeatRate(): string
    {
        return $this->repeatRate;
    }

    public function getReviewRate(): string
    {
        return $this->reviewRate;
    }

    public function getAverageOrdersPerCustomer(): string
    {
        return $this->averageOrdersPerCustomer;
    }

    public function getSalesDate(): \DateTimeImmutable
    {
        return $this->salesDate;
    }
}
