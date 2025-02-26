<?php

namespace App\Tests\Integration\Entity;

use App\Enum\DomainEventType;
use App\Factory\SupplierProductFactory;
use App\Factory\SupplierStockChangeLogFactory;
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
        $supplierProduct = SupplierProductFactory::createOne([
            'name' => 'Test Product',
            'stock' => 100,
            'cost' => '50.00',
        ]);
        $supplierStockChangeLog = SupplierStockChangeLogFactory::createOne([
            'eventType' => DomainEventType::SUPPLIER_PRODUCT_STOCK_CHANGED,
            'supplierProduct' => $supplierProduct,
            'eventTimestamp' => new \DateTimeImmutable(),
        ]);

        $errors = $this->validator->validate($supplierStockChangeLog);
        $this->assertCount(0, $errors);
    }

    public function testInvalidEventType(): void
    {
        $supplierStockChangeLog = SupplierStockChangeLogFactory::new()->withoutPersisting()->create([
            'eventType' => DomainEventType::ORDER_STATUS_CHANGED
        ]);

        $violations = $this->validator->validate($supplierStockChangeLog);
        $this->assertSame('Invalid event type', $violations[0]->getMessage());
    }

    public function testStockIsRequired(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['stock' => -1]);
        $supplierStockChangeLog = SupplierStockChangeLogFactory::new()->withoutPersisting()->create([
            'supplierProduct' => $supplierProduct
        ]);

        $violations = $this->validator->validate($supplierStockChangeLog);
        $this->assertSame('Please enter a stock level', $violations[0]->getMessage());
    }

    public function testSupplierStockChangeLogPersistence(): void
    {
        $supplierProduct = SupplierProductFactory::createOne([
            'name' => 'Test Product',
            'stock' => 100,
            'cost' => '50.00',
        ]);
        $supplierStockChangeLog = SupplierStockChangeLogFactory::createOne([
            'eventType' => DomainEventType::SUPPLIER_PRODUCT_STOCK_CHANGED,
            'supplierProduct' => $supplierProduct,
            'eventTimestamp' => new \DateTimeImmutable(),
        ])->_real();

        $persistedSupplierStockChangeLog = SupplierStockChangeLogFactory::repository()
            ->find($supplierStockChangeLog->getId())->_real();

        $this->assertEquals(100, $persistedSupplierStockChangeLog->getStock());
    }
}