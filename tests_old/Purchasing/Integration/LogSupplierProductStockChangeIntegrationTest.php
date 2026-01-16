<?php

namespace App\Tests\Purchasing\Integration;

use App\Shared\Domain\Event\DomainEventType;
use Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use tests\Shared\Factory\SupplierProductFactory;
use tests\Shared\Factory\SupplierStockChangeLogFactory;
use Zenstruck\Foundry\Test\Factories;

class LogSupplierProductStockChangeIntegrationTest extends KernelTestCase
{
    use Factories;

    protected function setUp(): void
    {
        self::bootKernel();
        StaffUserStory::load();
    }

    public function testOnSupplierProductStockChangeLogsStockChange(): void
    {
        $supplierProduct = SupplierProductFactory::createOne();

        $supplierProductStockChangeLog = SupplierStockChangeLogFactory::repository()->findOneBy([
            'supplierProductId' => $supplierProduct->getId(),
            'eventType' => DomainEventType::SUPPLIER_PRODUCT_STOCK_CHANGED,
        ]);

        $this->assertNotNull($supplierProductStockChangeLog);
    }
}
