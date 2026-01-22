<?php

namespace App\Tests\Reporting\Application\Handler;

use App\Reporting\Application\Handler\CalculateProductSalesSummaryHandler;
use App\Reporting\Domain\Metric\SalesDuration;
use App\Reporting\Domain\Metric\SalesType;
use App\Reporting\Domain\Model\SalesType\ProductSalesSummary;
use App\Reporting\Domain\Model\SalesType\ProductSalesType;
use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\ManufacturerFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\ProductSalesFactory;
use App\Tests\Shared\Factory\ProductSalesSummaryFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class CalculateProductSalesSummaryHandlerTest extends KernelTestCase
{
    use Factories;

    private CalculateProductSalesSummaryHandler $handler;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(CalculateProductSalesSummaryHandler::class);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testProcessCreatesAllSalesTypeSummaries(): void
    {
        $today = new \DateTime()->format('Y-m-d');
        $category = CategoryFactory::createOne();
        $manufacturer = ManufacturerFactory::createOne();
        $supplier = SupplierFactory::createOne();
        $product = ProductFactory::createOne([
            'category' => $category,
            'manufacturer' => $manufacturer,
        ]);

        ProductSalesFactory::createOne([
            'product' => $product,
            'supplier' => $supplier,
            'dateString' => $today,
            'salesQty' => 10,
            'salesCost' => '50.00',
            'salesValue' => '100.00',
        ]);

        $this->handler->process();

        $summaries = $this->em->getRepository(ProductSalesSummary::class)->findAll();
        $salesTypeValues = array_unique(array_map(fn ($s) => $s->getSalesType()->value, $summaries));

        foreach (SalesType::cases() as $salesType) {
            self::assertContains($salesType->value, $salesTypeValues, "Missing sales type: {$salesType->value}");
        }
    }

    public function testProcessSkipsDayDurationForProductSalesType(): void
    {
        $today = new \DateTime()->format('Y-m-d');
        $product = ProductFactory::createOne();
        $supplier = SupplierFactory::createOne();

        ProductSalesFactory::createOne([
            'product' => $product,
            'supplier' => $supplier,
            'dateString' => $today,
            'salesQty' => 10,
        ]);

        $this->handler->process();

        $summaries = $this->em->getRepository(ProductSalesSummary::class)->findBy([
            'salesType' => SalesType::PRODUCT,
            'duration' => SalesDuration::DAY,
        ]);

        self::assertCount(0, $summaries);
    }

    public function testProcessSkipsWeekAgoDurationForProductSalesType(): void
    {
        $weekAgo = new \DateTime()->modify('-7 days')->format('Y-m-d');
        $product = ProductFactory::createOne();
        $supplier = SupplierFactory::createOne();

        ProductSalesFactory::createOne([
            'product' => $product,
            'supplier' => $supplier,
            'dateString' => $weekAgo,
            'salesQty' => 10,
        ]);

        $this->handler->process();

        $summaries = $this->em->getRepository(ProductSalesSummary::class)->findBy([
            'salesType' => SalesType::PRODUCT,
            'duration' => SalesDuration::WEEK_AGO,
        ]);

        self::assertCount(0, $summaries);
    }

    public function testProcessWithRebuildReplacesExistingData(): void
    {
        $today = new \DateTime()->format('Y-m-d');
        $product = ProductFactory::createOne();
        $supplier = SupplierFactory::createOne();

        ProductSalesSummaryFactory::createOne([
            'productSalesType' => ProductSalesType::create(SalesType::CATEGORY, SalesDuration::LAST_30),
            'salesId' => $product->getCategory()->getId(),
            'dateString' => SalesDuration::LAST_30->getStartDate(),
            'salesQty' => 999,
            'salesCost' => 9999.00,
            'salesValue' => 19999.00,
        ]);

        $this->em->clear();

        ProductSalesFactory::createOne([
            'product' => $product,
            'supplier' => $supplier,
            'dateString' => $today,
            'salesQty' => 5,
            'salesCost' => '25.00',
            'salesValue' => '50.00',
        ]);

        $this->handler->process(rebuild: true);

        $summaries = $this->em->getRepository(ProductSalesSummary::class)->findBy([
            'salesType' => SalesType::CATEGORY,
            'duration' => SalesDuration::LAST_30,
        ]);

        self::assertCount(1, $summaries);
        self::assertNotSame(999, $summaries[0]->getSalesQty());
    }

    public function testProcessAggregatesByCategory(): void
    {
        $today = new \DateTime()->format('Y-m-d');
        $category = CategoryFactory::createOne();
        $supplier = SupplierFactory::createOne();

        $product1 = ProductFactory::createOne(['category' => $category]);
        $product2 = ProductFactory::createOne(['category' => $category]);

        ProductSalesFactory::createOne([
            'product' => $product1,
            'supplier' => $supplier,
            'dateString' => $today,
            'salesQty' => 10,
            'salesCost' => '50.00',
            'salesValue' => '100.00',
        ]);

        ProductSalesFactory::createOne([
            'product' => $product2,
            'supplier' => $supplier,
            'dateString' => $today,
            'salesQty' => 5,
            'salesCost' => '25.00',
            'salesValue' => '50.00',
        ]);

        $this->handler->process();

        $summaries = $this->em->getRepository(ProductSalesSummary::class)->findBy([
            'salesType' => SalesType::CATEGORY,
            'duration' => SalesDuration::TODAY,
        ]);

        self::assertCount(1, $summaries);
        self::assertSame(15, $summaries[0]->getSalesQty());
        self::assertSame('75.00', $summaries[0]->getSalesCost());
        self::assertSame('150.00', $summaries[0]->getSalesValue());
    }

    public function testProcessAggregatesByManufacturer(): void
    {
        $today = new \DateTime()->format('Y-m-d');
        $manufacturer = ManufacturerFactory::createOne();
        $supplier = SupplierFactory::createOne();

        $product1 = ProductFactory::createOne(['manufacturer' => $manufacturer]);
        $product2 = ProductFactory::createOne(['manufacturer' => $manufacturer]);

        ProductSalesFactory::createOne([
            'product' => $product1,
            'supplier' => $supplier,
            'dateString' => $today,
            'salesQty' => 8,
            'salesCost' => '40.00',
            'salesValue' => '80.00',
        ]);

        ProductSalesFactory::createOne([
            'product' => $product2,
            'supplier' => $supplier,
            'dateString' => $today,
            'salesQty' => 12,
            'salesCost' => '60.00',
            'salesValue' => '120.00',
        ]);

        $this->handler->process();

        $summaries = $this->em->getRepository(ProductSalesSummary::class)->findBy([
            'salesType' => SalesType::MANUFACTURER,
            'duration' => SalesDuration::TODAY,
        ]);

        self::assertCount(1, $summaries);
        self::assertSame(20, $summaries[0]->getSalesQty());
    }

    public function testProcessAggregatesBySupplier(): void
    {
        $today = new \DateTime()->format('Y-m-d');
        $supplier = SupplierFactory::createOne();

        $product1 = ProductFactory::createOne();
        $product2 = ProductFactory::createOne();

        ProductSalesFactory::createOne([
            'product' => $product1,
            'supplier' => $supplier,
            'dateString' => $today,
            'salesQty' => 7,
            'salesCost' => '35.00',
            'salesValue' => '70.00',
        ]);

        ProductSalesFactory::createOne([
            'product' => $product2,
            'supplier' => $supplier,
            'dateString' => $today,
            'salesQty' => 3,
            'salesCost' => '15.00',
            'salesValue' => '30.00',
        ]);

        $this->handler->process();

        $summaries = $this->em->getRepository(ProductSalesSummary::class)->findBy([
            'salesType' => SalesType::SUPPLIER,
            'duration' => SalesDuration::TODAY,
        ]);

        self::assertCount(1, $summaries);
        self::assertSame(10, $summaries[0]->getSalesQty());
    }

    public function testProcessWithNoSourceDataCreatesNoSummaries(): void
    {
        $this->handler->process();

        $summaries = $this->em->getRepository(ProductSalesSummary::class)->findAll();
        self::assertCount(0, $summaries);
    }

    public function testProcessAggregatesByAllType(): void
    {
        $today = new \DateTime()->format('Y-m-d');
        $supplier = SupplierFactory::createOne();

        $product1 = ProductFactory::createOne();
        $product2 = ProductFactory::createOne();

        ProductSalesFactory::createOne([
            'product' => $product1,
            'supplier' => $supplier,
            'dateString' => $today,
            'salesQty' => 100,
            'salesCost' => '500.00',
            'salesValue' => '1000.00',
        ]);

        ProductSalesFactory::createOne([
            'product' => $product2,
            'supplier' => $supplier,
            'dateString' => $today,
            'salesQty' => 50,
            'salesCost' => '250.00',
            'salesValue' => '500.00',
        ]);

        $this->handler->process();

        $summaries = $this->em->getRepository(ProductSalesSummary::class)->findBy([
            'salesType' => SalesType::ALL,
            'duration' => SalesDuration::TODAY,
        ]);

        self::assertCount(1, $summaries);
        self::assertSame(150, $summaries[0]->getSalesQty());
        self::assertSame('750.00', $summaries[0]->getSalesCost());
        self::assertSame('1500.00', $summaries[0]->getSalesValue());
    }
}
