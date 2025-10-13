<?php

namespace App\Tests\Integration\Service\Sales;

use App\Purchasing\Application\DTO\ChangePurchaseOrderItemStatusDto;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Reporting\Application\Handler\CalculateProductSalesHandler;
use App\Reporting\Domain\Model\SalesType\ProductSales;
use App\Service\PurchaseOrder\ChangePurchaseOrderItemStatus;
use Doctrine\ORM\EntityManagerInterface;
use tests\Shared\Factory\ProductSalesFactory;
use tests\Shared\Factory\PurchaseOrderItemFactory;
use tests\Shared\Factory\SupplierProductFactory;
use Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;
use function Zenstruck\Foundry\Persistence\save;

class ProductSalesCalculatorIntegrationTest extends KernelTestCase
{
    use Factories;

    private CalculateProductSalesHandler $productSalesCalculator;

    private ChangePurchaseOrderItemStatus $changePurchaseOrderItemStatus;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->changePurchaseOrderItemStatus = static::getContainer()->get(ChangePurchaseOrderItemStatus::class);
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $this->productSalesCalculator = new CalculateProductSalesHandler($em, $validator);
        StaffUserStory::load();
    }

    public function testProcessSuccessfully(): void
    {
        $date = new \DateTime()->format('Y-m-d');

        $supplierProduct = SupplierProductFactory::createOne([
            'cost' => '10.00',
            'stock' => 100,
        ]);

        PurchaseOrderItemFactory::createMany(10, [
            'product' => $supplierProduct->getProduct(),
            'supplier' => $supplierProduct->getSupplier(),
            'supplierProduct' => $supplierProduct,
        ]);

        $this->deliverPurchaseOrderItems();

        $this->productSalesCalculator->process($date);

        $productSales = ProductSalesFactory::repository()->findOneBy(['dateString' => $date]);
        $this->assertInstanceOf(ProductSales::class, $productSales);
        $this->assertSame(10, $productSales->getSalesQty());
        $this->assertSame('105.00', $productSales->getSalesValue());
        $this->assertSame('100.00', $productSales->getSalesCost());
    }

    private function deliverPurchaseOrderItems(): void
    {
        $purchaseOrderItems = PurchaseOrderItemFactory::repository()->findAll();
        foreach ($purchaseOrderItems as $purchaseOrderItem) {
            save($purchaseOrderItem);
            $this->changePurchaseOrderItemStatus($purchaseOrderItem, PurchaseOrderStatus::PROCESSING);
            $this->changePurchaseOrderItemStatus($purchaseOrderItem, PurchaseOrderStatus::ACCEPTED);
            $this->changePurchaseOrderItemStatus($purchaseOrderItem, PurchaseOrderStatus::SHIPPED);
            $this->changePurchaseOrderItemStatus($purchaseOrderItem, PurchaseOrderStatus::DELIVERED);
        }
    }

    private function changePurchaseOrderItemStatus(
        PurchaseOrderItem $purchaseOrderItem,
        PurchaseOrderStatus $newStatus
    ): void {
        $dto = new ChangePurchaseOrderItemStatusDto($purchaseOrderItem->getId(), $newStatus);
        $this->changePurchaseOrderItemStatus->fromDto($dto);
    }
}
