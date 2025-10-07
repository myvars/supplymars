<?php

namespace App\Tests\Integration\Entity;

use App\Enum\DomainEventType;
use App\Factory\SupplierProductFactory;
use App\Factory\SupplierStockChangeLogFactory;
use App\ValueObject\CostChange;
use App\ValueObject\StockChange;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class SupplierStockChangeLogIntegrationTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidSupplierStockChangeLog(): void
    {
        $supplierStockChangeLog = SupplierStockChangeLogFactory::createOne([
            'type' => DomainEventType::SUPPLIER_PRODUCT_STOCK_CHANGED,
            'supplierProductId' => 1,
            'occurredAt' => new \DateTimeImmutable(),
        ]);

        $errors = $this->validator->validate($supplierStockChangeLog);
        $this->assertCount(0, $errors);
    }

    public function testInvalidEventType(): void
    {
        $supplierStockChangeLog = SupplierStockChangeLogFactory::new()->withoutPersisting()->create([
            'type' => DomainEventType::ORDER_STATUS_CHANGED
        ]);

        $violations = $this->validator->validate($supplierStockChangeLog);
        $this->assertSame('Invalid event type', $violations[0]->getMessage());
    }

    public function testSupplierStockChangeLogPersistence(): void
    {
        $supplierStockChangeLog = SupplierStockChangeLogFactory::createOne([
            'type' => DomainEventType::SUPPLIER_PRODUCT_STOCK_CHANGED,
            'supplierProductId' => 1,
            'stockChange' => StockChange::from(0, 100),
            'costChange' => CostChange::from('0.00', '50.00'),
            'occurredAt' => new \DateTimeImmutable(),
        ])->_real();

        $persistedSupplierStockChangeLog = SupplierStockChangeLogFactory::repository()
            ->find($supplierStockChangeLog->getId())->_real();

        $this->assertEquals(100, $persistedSupplierStockChangeLog->getStock());
    }
}
