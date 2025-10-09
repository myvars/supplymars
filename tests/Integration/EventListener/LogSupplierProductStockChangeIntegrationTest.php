<?php

namespace App\Tests\Integration\EventListener;

use App\Enum\DomainEventType;
use App\Factory\SupplierProductFactory;
use App\Factory\SupplierStockChangeLogFactory;
use App\Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
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
