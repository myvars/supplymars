<?php

namespace App\Reporting\Domain\Model\SalesType;

use App\Reporting\Infrastructure\Persistence\Doctrine\CustomerSalesDoctrineRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CustomerSalesDoctrineRepository::class)]
class CustomerSales
{
    final private function __construct(
        #[ORM\Id]
        #[ORM\Column]
        #[Assert\Positive(message: 'Customer ID must be positive')]
        private readonly int $customerId,

        #[ORM\Id]
        #[ORM\Column(length: 10)]
        #[Assert\NotBlank(message: 'Date string must not be blank')]
        #[Assert\Length(max: 10)]
        private readonly string $dateString,

        #[ORM\Column]
        #[Assert\PositiveOrZero(message: 'Order count must be zero or positive')]
        private readonly int $orderCount,

        #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
        #[Assert\NotBlank(message: 'Order value must not be blank')]
        #[Assert\PositiveOrZero(message: 'Order value must be zero or positive')]
        private readonly string $orderValue,

        #[ORM\Column]
        #[Assert\PositiveOrZero(message: 'Item count must be zero or positive')]
        private readonly int $itemCount,

        #[ORM\Column(type: Types::DATE_IMMUTABLE)]
        #[Assert\NotNull(message: 'Sales date must not be null')]
        private readonly \DateTimeImmutable $salesDate,
    ) {
    }

    public static function create(
        int $customerId,
        string $dateString,
        int $orderCount,
        string $orderValue,
        int $itemCount,
    ): self {
        return new self(
            $customerId,
            $dateString,
            $orderCount,
            $orderValue,
            $itemCount,
            new \DateTimeImmutable($dateString),
        );
    }

    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    public function getDateString(): string
    {
        return $this->dateString;
    }

    public function getOrderCount(): int
    {
        return $this->orderCount;
    }

    public function getOrderValue(): string
    {
        return $this->orderValue;
    }

    public function getItemCount(): int
    {
        return $this->itemCount;
    }

    public function getSalesDate(): \DateTimeImmutable
    {
        return $this->salesDate;
    }
}
