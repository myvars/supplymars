<?php

namespace App\Tests\Audit\UI;

use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Shared\Domain\Event\DomainEventType;
use App\Shared\Domain\ValueObject\CostChange;
use App\Shared\Domain\ValueObject\StockChange;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\SupplierStockChangeLogFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class ProductHistoryFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testPageLoadsWithCharts(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        $product = ProductFactory::new()->withActiveSource()->create();
        $supplierProduct = $product->getSupplierProducts()->first();
        \assert($supplierProduct instanceof SupplierProduct);
        $publicId = $product->getPublicId()->value();

        SupplierStockChangeLogFactory::createMany(5, [
            'supplierProductId' => $supplierProduct->getId(),
            'type' => DomainEventType::SUPPLIER_PRODUCT_STOCK_CHANGED,
            'stockChange' => StockChange::from(0, 100),
            'costChange' => CostChange::from('0.00', '10.50'),
            'occurredAt' => new \DateTimeImmutable('-5 days'),
        ]);

        $this->browser()
            ->actingAs($user)
            ->get('/product/' . $publicId . '/history')
            ->assertSuccessful()
            ->assertSee('Stock History')
            ->assertSee('Cost History');
    }

    public function testEmptyStateWhenNoHistory(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        $product = ProductFactory::createOne();
        $publicId = $product->getPublicId()->value();

        $this->browser()
            ->actingAs($user)
            ->get('/product/' . $publicId . '/history')
            ->assertSuccessful()
            ->assertSee('No stock or cost history yet');
    }

    public function testDurationFilterChangesUrl(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        $product = ProductFactory::new()->withActiveSource()->create();
        $supplierProduct = $product->getSupplierProducts()->first();
        \assert($supplierProduct instanceof SupplierProduct);
        $publicId = $product->getPublicId()->value();

        SupplierStockChangeLogFactory::createOne([
            'supplierProductId' => $supplierProduct->getId(),
            'type' => DomainEventType::SUPPLIER_PRODUCT_STOCK_CHANGED,
            'stockChange' => StockChange::from(0, 100),
            'costChange' => CostChange::from('0.00', '10.50'),
            'occurredAt' => new \DateTimeImmutable('-3 days'),
        ]);

        $this->browser()
            ->actingAs($user)
            ->get('/product/' . $publicId . '/history?duration=last7')
            ->assertSuccessful()
            ->assertSee('Stock History');
    }

    public function testMultipleSuppliersShowData(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        $sp1 = SupplierProductFactory::createOne();
        $product = $sp1->getProduct();
        $sp2 = SupplierProductFactory::createOne(['product' => $product]);
        $publicId = $product->getPublicId()->value();

        foreach ([$sp1, $sp2] as $sp) {
            SupplierStockChangeLogFactory::createOne([
                'supplierProductId' => $sp->getId(),
                'type' => DomainEventType::SUPPLIER_PRODUCT_STOCK_CHANGED,
                'stockChange' => StockChange::from(0, 50),
                'costChange' => CostChange::from('0.00', '5.00'),
                'occurredAt' => new \DateTimeImmutable('-3 days'),
            ]);
        }

        $this->browser()
            ->actingAs($user)
            ->get('/product/' . $publicId . '/history')
            ->assertSuccessful()
            ->assertSee('Stock History')
            ->assertSee('Cost History');
    }
}
