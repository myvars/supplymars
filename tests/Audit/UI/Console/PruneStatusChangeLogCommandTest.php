<?php

declare(strict_types=1);

namespace App\Tests\Audit\UI\Console;

use App\Order\Domain\Model\Order\OrderStatus;
use App\Purchasing\Application\Service\ProcessingSimulator;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Shared\Domain\Event\DomainEventType;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\PurchaseOrderFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Factory\StatusChangeLogFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;

use function Zenstruck\Foundry\force;

#[WithStory(StaffUserStory::class)]
final class PruneStatusChangeLogCommandTest extends KernelTestCase
{
    public function testDeletesOldRowsOfFinishedEntitiesOnly(): void
    {
        $deliveredOrder = CustomerOrderFactory::createOne(['status' => force(OrderStatus::DELIVERED)]);
        $openOrder = CustomerOrderFactory::createOne(['status' => force(OrderStatus::PROCESSING)]);
        $cancelledOrderItem = CustomerOrderItemFactory::createOne(['status' => force(OrderStatus::CANCELLED)]);
        $refundedPurchaseOrder = PurchaseOrderFactory::createOne(['status' => force(PurchaseOrderStatus::REFUNDED)]);
        $deliveredItem = PurchaseOrderItemFactory::createOne(['status' => force(PurchaseOrderStatus::DELIVERED)]);
        $openItem = PurchaseOrderItemFactory::createOne(['status' => force(PurchaseOrderStatus::ACCEPTED)]);
        $openOrderItem = CustomerOrderItemFactory::createOne(['status' => force(OrderStatus::SHIPPED)]);
        $rejectedPurchaseOrder = PurchaseOrderFactory::createOne(['status' => force(PurchaseOrderStatus::REJECTED)]);

        $deleted = [
            $this->log(DomainEventType::ORDER_STATUS_CHANGED, (int) $deliveredOrder->getId(), '-13 months'),
            $this->log(DomainEventType::ORDER_ITEM_STATUS_CHANGED, (int) $cancelledOrderItem->getId(), '-13 months'),
            $this->log(DomainEventType::PURCHASE_ORDER_STATUS_CHANGED, (int) $refundedPurchaseOrder->getId(), '-13 months'),
            $this->log(DomainEventType::PURCHASE_ORDER_ITEM_STATUS_CHANGED, (int) $deliveredItem->getId(), '-13 months'),
        ];
        // Written in time order, as the logger writes them
        $kept = [
            // Older than the cutoff, but still open
            $this->log(DomainEventType::ORDER_STATUS_CHANGED, (int) $openOrder->getId(), '-13 months'),
            $this->log(DomainEventType::PURCHASE_ORDER_ITEM_STATUS_CHANGED, (int) $openItem->getId(), '-13 months'),
            $this->log(DomainEventType::ORDER_ITEM_STATUS_CHANGED, (int) $openOrderItem->getId(), '-13 months'),
            $this->log(DomainEventType::PURCHASE_ORDER_STATUS_CHANGED, (int) $rejectedPurchaseOrder->getId(), '-13 months'),
            // Finished, but newer than the cutoff
            $this->log(DomainEventType::ORDER_STATUS_CHANGED, (int) $deliveredOrder->getId(), '-11 months'),
            $this->log(DomainEventType::PURCHASE_ORDER_ITEM_STATUS_CHANGED, (int) $deliveredItem->getId(), '-1 day'),
        ];

        $result = self::runCommand('app:prune-status-change-log');

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('Deleted 4 status change log rows', $result->getDisplay());

        // The delete runs in SQL, so read the rows back from the database rather than the identity map
        self::getContainer()->get(EntityManagerInterface::class)->clear();
        foreach ($deleted as $id) {
            self::assertNull(StatusChangeLogFactory::repository()->find($id));
        }

        foreach ($kept as $id) {
            self::assertNotNull(StatusChangeLogFactory::repository()->find($id));
        }
    }

    public function testDeletesAcrossBatches(): void
    {
        $deliveredItem = PurchaseOrderItemFactory::createOne(['status' => force(PurchaseOrderStatus::DELIVERED)]);
        foreach (['-15 months', '-14 months', '-13 months'] as $ago) {
            $this->log(DomainEventType::PURCHASE_ORDER_ITEM_STATUS_CHANGED, (int) $deliveredItem->getId(), $ago);
        }

        $result = self::runCommand('app:prune-status-change-log', ['--batch-size' => 2]);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('Deleted 3 status change log rows', $result->getDisplay());
        self::assertSame(0, StatusChangeLogFactory::count());
    }

    public function testSimulatorStillShipsAndDeliversOpenItemsAfterAPrune(): void
    {
        $acceptedItem = PurchaseOrderItemFactory::createOne(['status' => force(PurchaseOrderStatus::ACCEPTED)]);
        $shippedItem = PurchaseOrderItemFactory::createOne(['status' => force(PurchaseOrderStatus::SHIPPED)]);
        $this->log(DomainEventType::PURCHASE_ORDER_ITEM_STATUS_CHANGED, (int) $acceptedItem->getId(), '-13 months', PurchaseOrderStatus::ACCEPTED);
        $this->log(DomainEventType::PURCHASE_ORDER_ITEM_STATUS_CHANGED, (int) $shippedItem->getId(), '-13 months', PurchaseOrderStatus::SHIPPED);

        self::runCommand('app:prune-status-change-log');

        /** @var ProcessingSimulator $simulator */
        $simulator = self::getContainer()->get(ProcessingSimulator::class);
        $noon = new \DateTimeImmutable('today 12:00:00');
        self::assertTrue($simulator->canShipTimingOnly($acceptedItem, $noon));
        self::assertTrue($simulator->canDeliverTimingOnly($shippedItem, $noon));
    }

    public function testInvalidOptionsReturnInvalid(): void
    {
        $result = self::runCommand('app:prune-status-change-log', ['--months' => 0]);

        $this->assertCommandIsInvalid($result);
    }

    private function log(
        DomainEventType $eventType,
        int $entityId,
        string $ago,
        PurchaseOrderStatus $status = PurchaseOrderStatus::DELIVERED,
    ): int {
        $log = StatusChangeLogFactory::createOne([
            'eventType' => $eventType,
            'eventTypeId' => $entityId,
            'status' => $status->value,
            'eventTimestamp' => new \DateTimeImmutable($ago),
        ]);

        return (int) $log->getId();
    }
}
