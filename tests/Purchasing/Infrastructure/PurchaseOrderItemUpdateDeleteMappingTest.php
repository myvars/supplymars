<?php

namespace App\Tests\Purchasing\Infrastructure;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\Domain\Repository\PurchaseOrderItemRepository;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

#[WithStory(StaffUserStory::class)]
final class PurchaseOrderItemUpdateDeleteMappingTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $em;

    private PurchaseOrderItemRepository $purchaseOrderItems;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->purchaseOrderItems = self::getContainer()->get(PurchaseOrderItemRepository::class);
    }

    public function testUpdateRoundTripPersistsChanges(): void
    {
        // Create customer order item with quantity 10 so we have room to increase PO item quantity
        $customerOrderItem = CustomerOrderItemFactory::createOne([
            'quantity' => 10,
        ]);

        $purchaseOrderItem = PurchaseOrderItemFactory::createOne([
            'customerOrder' => $customerOrderItem->getCustomerOrder(),
            'customerOrderItem' => $customerOrderItem,
            'product' => $customerOrderItem->getProduct(),
            'quantity' => 5,
        ]);

        $this->em->flush();
        $this->em->clear();

        $id = $purchaseOrderItem->getId();
        $loaded = $this->em->getRepository(PurchaseOrderItem::class)->find($id);
        self::assertNotNull($loaded);
        self::assertSame(5, $loaded->getQuantity());

        $loaded->updateItemQuantity(8);

        $this->em->flush();
        $this->em->clear();

        $reloaded = $this->em->getRepository(PurchaseOrderItem::class)->find($id);
        self::assertNotNull($reloaded);
        self::assertSame(8, $reloaded->getQuantity());
        self::assertSame(bcmul('8', $reloaded->getPrice() ?? '0', 2), $reloaded->getTotalPrice());
    }

    public function testAllMappedColumnsRoundTripCorrectly(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne([
            'quantity' => 3,
        ]);

        $this->em->flush();
        $this->em->clear();

        $id = $purchaseOrderItem->getId();
        $loaded = $this->em->getRepository(PurchaseOrderItem::class)->find($id);

        self::assertNotNull($loaded);
        self::assertSame(3, $loaded->getQuantity());
        self::assertNotEmpty($loaded->getPrice());
        self::assertNotEmpty($loaded->getPriceIncVat());
        self::assertGreaterThan(0, $loaded->getWeight());
        self::assertNotEmpty($loaded->getTotalPrice());
        self::assertNotEmpty($loaded->getTotalPriceIncVat());
        self::assertGreaterThan(0, $loaded->getTotalWeight());
        self::assertSame(PurchaseOrderStatus::PENDING, $loaded->getStatus());
        self::assertNotNull($loaded->getPurchaseOrder());
        self::assertNotNull($loaded->getSupplierProduct());
        self::assertNotNull($loaded->getCustomerOrderItem());
    }

    public function testDeleteRemovesRow(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();

        $this->em->flush();

        $id = $purchaseOrderItem->getId();

        $loaded = $this->em->getRepository(PurchaseOrderItem::class)->find($id);
        self::assertNotNull($loaded);

        $this->purchaseOrderItems->remove($loaded);
        $this->em->flush();
        $this->em->clear();

        self::assertNull($this->em->getRepository(PurchaseOrderItem::class)->find($id));
    }
}
