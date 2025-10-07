<?php

namespace App\Tests\Integration\EventListener;

use App\Enum\DomainEventType;
use App\Factory\SupplierProductFactory;
use App\Factory\SupplierStockChangeLogFactory;
use App\Service\Utility\DomainEventDispatcher;
use App\Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class LogSupplierProductStockChangeIntegrationTest extends KernelTestCase
{
    use Factories;

    private DomainEventDispatcher $domainEventDispatcher;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->domainEventDispatcher = static::getContainer()->get(DomainEventDispatcher::class);
        StaffUserStory::load();
    }

    public function testOnSupplierProductStockChangeLogsStockChange(): void
    {
        $supplierProduct = SupplierProductFactory::createOne()->_real();

        $this->domainEventDispatcher->dispatchProviderEvents($supplierProduct);

        $supplierProductStockChangeLog = SupplierStockChangeLogFactory::repository()->findOneBy([
            'supplierProductId' => $supplierProduct->getId(),
            'eventType' => DomainEventType::SUPPLIER_PRODUCT_STOCK_CHANGED,
        ]);

        $this->assertNotNull($supplierProductStockChangeLog);
    }
}
