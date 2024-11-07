<?php

namespace App\Entity;

use App\Repository\ProductSalesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductSalesRepository::class)]
class ProductSales
{
    private function __construct(
        #[ORM\Id]
        #[ORM\ManyToOne]
        #[ORM\JoinColumn(nullable: false)]
        private readonly Product $product,
        #[ORM\Id]
        #[ORM\ManyToOne]
        #[ORM\JoinColumn(nullable: false)]
        private readonly Supplier $supplier,
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
        Product $product,
        Supplier $supplier,
        string $dateString,
        int $salesQty,
        string $salesCost,
        string $salesValue,
    ): self{
        return new self($product, $supplier, $dateString, $salesQty, $salesCost, $salesValue, new \DateTimeImmutable($dateString));
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
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
