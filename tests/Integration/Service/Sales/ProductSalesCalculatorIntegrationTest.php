<?php

namespace App\Tests\Integration\Service\Sales;

use App\DTO\ChangePurchaseOrderItemStatusDto;
use App\Entity\ProductSales;
use App\Entity\PurchaseOrderItem;
use App\Enum\PurchaseOrderStatus;
use App\Factory\ProductSalesFactory;
use App\Factory\PurchaseOrderItemFactory;
use App\Factory\SupplierProductFactory;
use App\Service\PurchaseOrder\ChangePurchaseOrderItemStatus;
use App\Service\Sales\ProductSalesCalculator;
use App\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class ProductSalesCalculatorIntegrationTest extends KernelTestCase
{
    use Factories;

    private ProductSalesCalculator $productSalesCalculator;
    private ChangePurchaseOrderItemStatus $changePurchaseOrderItemStatus;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->changePurchaseOrderItemStatus = static::getContainer()->get(ChangePurchaseOrderItemStatus::class);
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $this->productSalesCalculator = new ProductSalesCalculator($entityManager, $validator);
        StaffUserStory::load();
    }

    public function testProcessSuccessfully(): void
    {
        $date = (new \DateTime())->format('Y-m-d');

        $supplierProduct = SupplierProductFactory::createOne([
            'cost' => '10.00',
            'stock' => 100,
        ])->_real();

        PurchaseOrderItemFactory::createMany(10, [
            'product' => $supplierProduct->getProduct(),
            'supplier' => $supplierProduct->getSupplier(),
            'supplierProduct' => $supplierProduct,
        ]);

        $this->deliverPurchaseOrderItems();

        $this->productSalesCalculator->process($date);

        $productSales = ProductSalesFactory::repository()->findOneBy(['dateString' => $date])->_real();
        $this->assertInstanceOf(ProductSales::class, $productSales);
        $this->assertSame(10, $productSales->getSalesQty());
        $this->assertSame('105.00', $productSales->getSalesValue());
        $this->assertSame('100.00', $productSales->getSalesCost());
    }

    private function deliverPurchaseOrderItems(): void
    {
        $purchaseOrderItems = PurchaseOrderItemFactory::repository()->findAll();
        foreach ($purchaseOrderItems as $purchaseOrderItem) {
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