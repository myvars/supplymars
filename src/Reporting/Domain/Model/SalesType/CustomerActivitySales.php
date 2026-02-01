<?php

namespace App\Reporting\Domain\Model\SalesType;

use App\Reporting\Infrastructure\Persistence\Doctrine\CustomerActivitySalesDoctrineRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CustomerActivitySalesDoctrineRepository::class)]
class CustomerActivitySales
{
    final private function __construct(
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

        #[ORM\Column(type: Types::DATE_IMMUTABLE)]
        #[Assert\NotNull(message: 'Sales date must not be null')]
        private readonly \DateTimeImmutable $salesDate,
    ) {
    }

    public static function create(
        string $dateString,
        int $totalCustomers,
        int $activeCustomers,
        int $newCustomers,
        int $returningCustomers,
    ): self {
        return new self(
            $dateString,
            $totalCustomers,
            $activeCustomers,
            $newCustomers,
            $returningCustomers,
            new \DateTimeImmutable($dateString),
        );
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

    public function getSalesDate(): \DateTimeImmutable
    {
        return $this->salesDate;
    }
}
