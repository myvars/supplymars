<?php

namespace App\Tests\Reporting\Application\Handler;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Reporting\Application\Handler\CalculateProductSalesHandler;
use App\Reporting\Domain\Model\SalesType\ProductSales;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\ProductSalesFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class CalculateProductSalesHandlerTest extends KernelTestCase
{
    use Factories;

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

        $purchaseOrderItem = PurchaseOrderItemFactory::createOne([
            'product' => $product,
            'supplier' => $supplier,
            'quantity' => 5,
        ]);

        $this->setDeliveredStatus($this->getEntity($purchaseOrderItem), new \DateTimeImmutable($date));

        $this->handler->process($date);

        $productSales = $this->em->getRepository(ProductSales::class)->findAll();
        self::assertCount(1, $productSales);
        self::assertSame($date, $productSales[0]->getDateString());
        self::assertSame($product->getId(), $productSales[0]->getProduct()->getId());
        self::assertSame($supplier->getId(), $productSales[0]->getSupplier()->getId());
    }

    public function testProcessIgnoresNonDeliveredItems(): void
    {
        $date = new \DateTime()->format('Y-m-d');

        PurchaseOrderItemFactory::createOne(['quantity' => 5]);

        $this->handler->process($date);

        $productSales = $this->em->getRepository(ProductSales::class)->findAll();
        self::assertCount(0, $productSales);
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

        $this->em->clear();

        $purchaseOrderItem = PurchaseOrderItemFactory::createOne([
            'product' => $product,
            'supplier' => $supplier,
            'quantity' => 3,
        ]);

        $this->setDeliveredStatus($this->getEntity($purchaseOrderItem), new \DateTimeImmutable($date));

        $this->handler->process($date);

        $productSales = $this->em->getRepository(ProductSales::class)->findAll();
        self::assertCount(1, $productSales);
        self::assertSame(3, $productSales[0]->getSalesQty());
    }

    public function testProcessCalculatesCorrectSalesMetrics(): void
    {
        $date = new \DateTime()->format('Y-m-d');
        $product = ProductFactory::createOne();
        $supplier = SupplierFactory::createOne();

        $purchaseOrderItem = PurchaseOrderItemFactory::createOne([
            'product' => $product,
            'supplier' => $supplier,
            'quantity' => 10,
        ]);

        $this->setDeliveredStatus($this->getEntity($purchaseOrderItem), new \DateTimeImmutable($date));

        $this->handler->process($date);

        $productSales = $this->em->getRepository(ProductSales::class)->findAll();
        self::assertCount(1, $productSales);

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

        $item1 = PurchaseOrderItemFactory::createOne([
            'product' => $product,
            'supplier' => $supplier,
            'quantity' => 5,
        ]);
        $this->setDeliveredStatus($this->getEntity($item1), new \DateTimeImmutable($date));

        $item2 = PurchaseOrderItemFactory::createOne([
            'product' => $product,
            'supplier' => $supplier,
            'quantity' => 3,
        ]);
        $this->setDeliveredStatus($this->getEntity($item2), new \DateTimeImmutable($date));

        $this->handler->process($date);

        $productSales = $this->em->getRepository(ProductSales::class)->findAll();
        self::assertCount(1, $productSales);
        self::assertSame(8, $productSales[0]->getSalesQty());
    }

    public function testProcessSeparatesSameProductFromDifferentSuppliers(): void
    {
        $date = new \DateTime()->format('Y-m-d');
        $product = ProductFactory::createOne();
        $supplier1 = SupplierFactory::createOne();
        $supplier2 = SupplierFactory::createOne();

        $item1 = PurchaseOrderItemFactory::createOne([
            'product' => $product,
            'supplier' => $supplier1,
            'quantity' => 5,
        ]);
        $this->setDeliveredStatus($this->getEntity($item1), new \DateTimeImmutable($date));

        $item2 = PurchaseOrderItemFactory::createOne([
            'product' => $product,
            'supplier' => $supplier2,
            'quantity' => 3,
        ]);
        $this->setDeliveredStatus($this->getEntity($item2), new \DateTimeImmutable($date));

        $this->handler->process($date);

        $productSales = $this->em->getRepository(ProductSales::class)->findAll();
        self::assertCount(2, $productSales);
    }

    public function testProcessWithNoDataCreatesNoRecords(): void
    {
        $date = '2000-01-01';

        $this->handler->process($date);

        $productSales = $this->em->getRepository(ProductSales::class)->findAll();
        self::assertCount(0, $productSales);
    }

    private function getEntity(mixed $proxyOrEntity): PurchaseOrderItem
    {
        if ($proxyOrEntity instanceof PurchaseOrderItem) {
            return $proxyOrEntity;
        }

        return $proxyOrEntity->_real();
    }

    private function setDeliveredStatus(PurchaseOrderItem $item, \DateTimeImmutable $deliveredAt): void
    {
        $statusProperty = new \ReflectionProperty(PurchaseOrderItem::class, 'status');
        $statusProperty->setValue($item, PurchaseOrderStatus::DELIVERED);

        $deliveredAtProperty = new \ReflectionProperty(PurchaseOrderItem::class, 'deliveredAt');
        $deliveredAtProperty->setValue($item, $deliveredAt);

        $this->em->flush();
    }
}
