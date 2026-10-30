<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Application\Handler;

use App\Reporting\Application\Handler\CalculateProductSalesHandler;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\ProductSalesFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(StaffUserStory::class)]
final class CalculateProductSalesHandlerTest extends KernelTestCase
{
    private CalculateProductSalesHandler $handler;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(CalculateProductSalesHandler::class);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testProcessCreatesProductSalesForDeliveredItems(): void
    {
        $date = new \DateTime()->format('Y-m-d');
        $product = ProductFactory::createOne();
        $supplier = SupplierFactory::createOne();

        PurchaseOrderItemFactory::new()->delivered()->create([
            'product' => $product,
            'supplier' => $supplier,
            'quantity' => 5,
        ]);

        $this->handler->process($date);

        ProductSalesFactory::assert()->count(1);
        $productSales = ProductSalesFactory::all();
        self::assertSame($date, $productSales[0]->getDateString());
        self::assertSame($product->getId(), $productSales[0]->getProduct()->getId());
        self::assertSame($supplier->getId(), $productSales[0]->getSupplier()->getId());
    }

    public function testProcessIgnoresNonDeliveredItems(): void
    {
        $date = new \DateTime()->format('Y-m-d');

        PurchaseOrderItemFactory::createOne(['quantity' => 5]);

        $this->handler->process($date);

        ProductSalesFactory::assert()->empty();
    }

    public function testProcessDeletesExistingRecordsBeforeCreating(): void
    {
        $date = new \DateTime()->format('Y-m-d');
        $product = ProductFactory::createOne();
        $supplier = SupplierFactory::createOne();

        ProductSalesFactory::createOne([
            'product' => $product,
            'supplier' => $supplier,
            'dateString' => $date,
            'salesQty' => 100,
        ]);

        PurchaseOrderItemFactory::new()->delivered()->create([
            'product' => $product,
            'supplier' => $supplier,
            'quantity' => 3,
        ]);

        $this->em->clear();

        $this->handler->process($date);

        ProductSalesFactory::assert()->count(1);
        $productSales = ProductSalesFactory::all();
        self::assertSame(3, $productSales[0]->getSalesQty());
    }

    public function testProcessCalculatesCorrectSalesMetrics(): void
    {
        $date = new \DateTime()->format('Y-m-d');
        $product = ProductFactory::createOne();
        $supplier = SupplierFactory::createOne();

        PurchaseOrderItemFactory::new()->delivered()->create([
            'product' => $product,
            'supplier' => $supplier,
            'quantity' => 10,
        ]);

        $this->handler->process($date);

        ProductSalesFactory::assert()->count(1);
        $productSales = ProductSalesFactory::all();

        $sales = $productSales[0];
        self::assertSame(10, $sales->getSalesQty());
        self::assertNotEmpty($sales->getSalesCost());
        self::assertNotEmpty($sales->getSalesValue());
    }

    public function testProcessAggregatesByProductAndSupplier(): void
    {
        $date = new \DateTime()->format('Y-m-d');
        $product = ProductFactory::createOne();
        $supplier = SupplierFactory::createOne();

        PurchaseOrderItemFactory::new()->delivered()->create([
            'product' => $product,
            'supplier' => $supplier,
            'quantity' => 5,
        ]);

        PurchaseOrderItemFactory::new()->delivered()->create([
            'product' => $product,
            'supplier' => $supplier,
            'quantity' => 3,
        ]);

        $this->handler->process($date);

        ProductSalesFactory::assert()->count(1);
        $productSales = ProductSalesFactory::all();
        self::assertSame(8, $productSales[0]->getSalesQty());
    }

    public function testProcessSeparatesSameProductFromDifferentSuppliers(): void
    {
        $date = new \DateTime()->format('Y-m-d');
        $product = ProductFactory::createOne();
        $supplier1 = SupplierFactory::createOne();
        $supplier2 = SupplierFactory::createOne();

        PurchaseOrderItemFactory::new()->delivered()->create([
            'product' => $product,
            'supplier' => $supplier1,
            'quantity' => 5,
        ]);

        PurchaseOrderItemFactory::new()->delivered()->create([
            'product' => $product,
            'supplier' => $supplier2,
            'quantity' => 3,
        ]);

        $this->handler->process($date);

        ProductSalesFactory::assert()->count(2);
    }

    public function testProcessWithNoDataCreatesNoRecords(): void
    {
        $date = '2000-01-01';

        $this->handler->process($date);

        ProductSalesFactory::assert()->empty();
    }
}
