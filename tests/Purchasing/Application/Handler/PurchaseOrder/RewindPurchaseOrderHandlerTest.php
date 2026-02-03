<?php

namespace App\Tests\Purchasing\Application\Handler\PurchaseOrder;

use App\Purchasing\Application\Command\PurchaseOrder\RewindPurchaseOrder;
use App\Purchasing\Application\Handler\PurchaseOrder\RewindPurchaseOrderHandler;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderPublicId;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\PurchaseOrderFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

final class RewindPurchaseOrderHandlerTest extends KernelTestCase
{
    use Factories;

    private RewindPurchaseOrderHandler $handler;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $handler = $container->get(RewindPurchaseOrderHandler::class);
        \assert($handler instanceof RewindPurchaseOrderHandler);
        $this->handler = $handler;

        $em = $container->get(EntityManagerInterface::class);
        \assert($em instanceof EntityManagerInterface);
        $this->em = $em;
    }

    #[WithStory(StaffUserStory::class)]
    public function testRewindsExistingPurchaseOrder(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
        ]);

        $order = CustomerOrderFactory::createOne();
        $orderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 5,
        ]);

        $purchaseOrder = PurchaseOrderFactory::createOne([
            'customerOrder' => $order,
            'supplier' => $supplier,
        ]);

        $purchaseOrderItem = PurchaseOrderItemFactory::createOne([
            'customerOrder' => $order,
            'customerOrderItem' => $orderItem,
            'purchaseOrder' => $purchaseOrder,
            'supplierProduct' => $supplierProduct,
            'product' => $product,
            'supplier' => $supplier,
            'quantity' => 5,
        ]);

        $purchaseOrderItem->updateItemStatus(PurchaseOrderStatus::PROCESSING);

        $this->em->flush();

        $command = new RewindPurchaseOrder($purchaseOrder->getPublicId());
        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('Purchase order rewound to pending.', $result->message);
        self::assertSame(PurchaseOrderStatus::PENDING, $purchaseOrder->getStatus());
    }

    #[WithStory(StaffUserStory::class)]
    public function testFailsWhenPurchaseOrderNotFound(): void
    {
        $missingId = PurchaseOrderPublicId::new();

        $command = new RewindPurchaseOrder($missingId);
        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Purchase order not found', $result->message);
    }
}
