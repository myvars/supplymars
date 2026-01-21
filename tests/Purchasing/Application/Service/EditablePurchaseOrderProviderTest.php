<?php

namespace App\Tests\Purchasing\Application\Service;

use App\Purchasing\Application\Service\EditablePurchaseOrderProvider;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\PurchaseOrderFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

final class EditablePurchaseOrderProviderTest extends KernelTestCase
{
    use Factories;

    private EditablePurchaseOrderProvider $provider;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->provider = self::getContainer()->get(EditablePurchaseOrderProvider::class);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    #[WithStory(StaffUserStory::class)]
    public function testReturnsExistingEditablePurchaseOrder(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $order = CustomerOrderFactory::createOne();

        $existingPO = PurchaseOrderFactory::createOne([
            'customerOrder' => $order,
            'supplier' => $supplier,
        ]);

        $result = $this->provider->getOrCreateForSupplier($order, $supplier);
        $this->em->flush();

        self::assertSame($existingPO->getId(), $result->getId());
        self::assertSame(PurchaseOrderStatus::PENDING, $result->getStatus());
    }

    #[WithStory(StaffUserStory::class)]
    public function testCreatesNewPurchaseOrderWhenNoneExists(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $order = CustomerOrderFactory::createOne();

        self::assertCount(0, $order->getPurchaseOrders());

        $result = $this->provider->getOrCreateForSupplier($order, $supplier);
        $this->em->flush();

        self::assertInstanceOf(PurchaseOrder::class, $result);
        self::assertSame($supplier->getId(), $result->getSupplier()->getId());
        self::assertSame($order->getId(), $result->getCustomerOrder()->getId());
        self::assertSame(PurchaseOrderStatus::PENDING, $result->getStatus());
        self::assertCount(1, $order->getPurchaseOrders());
    }

    #[WithStory(StaffUserStory::class)]
    public function testReturnsCorrectPOWhenMultipleSuppliersExist(): void
    {
        $supplier1 = SupplierFactory::createOne(['isActive' => true]);
        $supplier2 = SupplierFactory::createOne(['isActive' => true]);
        $order = CustomerOrderFactory::createOne();

        $po1 = PurchaseOrderFactory::createOne([
            'customerOrder' => $order,
            'supplier' => $supplier1,
        ]);
        $po2 = PurchaseOrderFactory::createOne([
            'customerOrder' => $order,
            'supplier' => $supplier2,
        ]);

        $result = $this->provider->getOrCreateForSupplier($order, $supplier2);
        $this->em->flush();

        self::assertSame($po2->getId(), $result->getId());
        self::assertNotSame($po1->getId(), $result->getId());
    }

    #[WithStory(StaffUserStory::class)]
    public function testSkipsNonEditablePurchaseOrders(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $order = CustomerOrderFactory::createOne();

        // Create a PO with an item, then transition item to PROCESSING to make PO non-editable
        $poItem = PurchaseOrderItemFactory::createOne([
            'customerOrder' => $order,
            'supplier' => $supplier,
        ]);

        $existingPO = $poItem->getPurchaseOrder();
        self::assertSame(PurchaseOrderStatus::PENDING, $existingPO->getStatus());

        // Transition the item to PROCESSING, which cascades to the PO
        $poItem->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        $this->em->flush();

        self::assertSame(PurchaseOrderStatus::PROCESSING, $existingPO->getStatus());
        self::assertFalse($existingPO->allowEdit());

        // Now calling the provider should create a new PO since the existing one is not editable
        $result = $this->provider->getOrCreateForSupplier($order, $supplier);
        $this->em->flush();

        self::assertNotSame($existingPO->getId(), $result->getId());
        self::assertSame(PurchaseOrderStatus::PENDING, $result->getStatus());
        self::assertTrue($result->allowEdit());
    }

    #[WithStory(StaffUserStory::class)]
    public function testCreatesSeparatePOsForDifferentSuppliers(): void
    {
        $supplier1 = SupplierFactory::createOne(['isActive' => true]);
        $supplier2 = SupplierFactory::createOne(['isActive' => true]);
        $order = CustomerOrderFactory::createOne();

        $result1 = $this->provider->getOrCreateForSupplier($order, $supplier1);
        $result2 = $this->provider->getOrCreateForSupplier($order, $supplier2);
        $this->em->flush();

        self::assertNotSame($result1->getId(), $result2->getId());
        self::assertSame($supplier1->getId(), $result1->getSupplier()->getId());
        self::assertSame($supplier2->getId(), $result2->getSupplier()->getId());
        self::assertCount(2, $order->getPurchaseOrders());
    }

    #[WithStory(StaffUserStory::class)]
    public function testIsIdempotent(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $order = CustomerOrderFactory::createOne();

        $result1 = $this->provider->getOrCreateForSupplier($order, $supplier);
        $this->em->flush();

        $result2 = $this->provider->getOrCreateForSupplier($order, $supplier);
        $this->em->flush();

        self::assertSame($result1->getId(), $result2->getId());
        self::assertCount(1, $order->getPurchaseOrders());
    }
}
