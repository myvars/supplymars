<?php

namespace App\Tests\Purchasing\Application\Service;

use App\Audit\Domain\Model\StatusChange\StatusChangeLog;
use App\Purchasing\Application\Service\ProcessingSimulator;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Shared\Domain\Event\DomainEventType;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Factory\StatusChangeLogFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

class ProcessingSimulatorTest extends KernelTestCase
{
    use Factories;

    private ProcessingSimulator $simulator;

    protected function setUp(): void
    {
        self::bootKernel();
        /** @var ProcessingSimulator $simulator */
        $simulator = self::getContainer()->get(ProcessingSimulator::class);
        $this->simulator = $simulator;
    }

    #[WithStory(StaffUserStory::class)]
    public function testCanShipReturnsFalseWithoutStatusChangeLog(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $poItem = PurchaseOrderItemFactory::createOne(['supplier' => $supplier]);

        // No status change log exists
        $result = $this->simulator->canShipTimingOnly($poItem, $this->createBusinessHoursTime(12));

        self::assertFalse($result);
    }

    #[WithStory(StaffUserStory::class)]
    public function testCanShipReturnsFalseOutsideBusinessHours(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $poItem = PurchaseOrderItemFactory::createOne(['supplier' => $supplier]);

        // Test at 7 AM (before business hours) - event is 3 hours before that
        $now7am = $this->createBusinessHoursTime(7);
        $this->createStatusChangeLogRelativeToNow($poItem, PurchaseOrderStatus::ACCEPTED, 3, $now7am);
        $result = $this->simulator->canShipTimingOnly($poItem, $now7am);
        self::assertFalse($result);
    }

    #[WithStory(StaffUserStory::class)]
    public function testCanShipReturnsFalseOutsideBusinessHoursEvening(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $poItem = PurchaseOrderItemFactory::createOne(['supplier' => $supplier]);

        // Test at 20 PM (after business hours)
        $now8pm = $this->createBusinessHoursTime(20);
        $this->createStatusChangeLogRelativeToNow($poItem, PurchaseOrderStatus::ACCEPTED, 3, $now8pm);
        $result = $this->simulator->canShipTimingOnly($poItem, $now8pm);
        self::assertFalse($result);
    }

    #[WithStory(StaffUserStory::class)]
    public function testCanShipReturnsFalseWithInsufficientWaitTime(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $poItem = PurchaseOrderItemFactory::createOne(['supplier' => $supplier]);

        // Create accepted status change log only 1 hour ago (need 2 hours)
        $now = $this->createBusinessHoursTime(12);
        $this->createStatusChangeLogRelativeToNow($poItem, PurchaseOrderStatus::ACCEPTED, 1, $now);

        $result = $this->simulator->canShipTimingOnly($poItem, $now);

        self::assertFalse($result);
    }

    #[WithStory(StaffUserStory::class)]
    public function testCanShipTimingOnlyReturnsTrueWhenAllConditionsMet(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $poItem = PurchaseOrderItemFactory::createOne(['supplier' => $supplier]);

        // Business hours (12 PM) with sufficient wait time (3 hours ago)
        $now = $this->createBusinessHoursTime(12);
        $this->createStatusChangeLogRelativeToNow($poItem, PurchaseOrderStatus::ACCEPTED, 3, $now);

        $result = $this->simulator->canShipTimingOnly($poItem, $now);

        self::assertTrue($result);
    }

    #[WithStory(StaffUserStory::class)]
    public function testCanDeliverReturnsFalseWithoutStatusChangeLog(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $poItem = PurchaseOrderItemFactory::createOne(['supplier' => $supplier]);

        // No shipped status change log exists
        $result = $this->simulator->canDeliverTimingOnly($poItem, $this->createBusinessHoursTime(12));

        self::assertFalse($result);
    }

    #[WithStory(StaffUserStory::class)]
    public function testCanDeliverReturnsFalseOutsideBusinessHours(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $poItem = PurchaseOrderItemFactory::createOne(['supplier' => $supplier]);

        // Test at 5 AM (before delivery hours 7-21)
        $now5am = $this->createBusinessHoursTime(5);
        $this->createStatusChangeLogRelativeToNow($poItem, PurchaseOrderStatus::SHIPPED, 13, $now5am);
        $result = $this->simulator->canDeliverTimingOnly($poItem, $now5am);
        self::assertFalse($result);
    }

    #[WithStory(StaffUserStory::class)]
    public function testCanDeliverReturnsFalseOutsideBusinessHoursEvening(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $poItem = PurchaseOrderItemFactory::createOne(['supplier' => $supplier]);

        // Test at 22 PM (after delivery hours)
        $now10pm = $this->createBusinessHoursTime(22);
        $this->createStatusChangeLogRelativeToNow($poItem, PurchaseOrderStatus::SHIPPED, 13, $now10pm);
        $result = $this->simulator->canDeliverTimingOnly($poItem, $now10pm);
        self::assertFalse($result);
    }

    #[WithStory(StaffUserStory::class)]
    public function testCanDeliverReturnsFalseWithInsufficientWaitTime(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $poItem = PurchaseOrderItemFactory::createOne(['supplier' => $supplier]);

        // Create shipped status change log only 6 hours ago (need 12 hours)
        $now = $this->createBusinessHoursTime(12);
        $this->createStatusChangeLogRelativeToNow($poItem, PurchaseOrderStatus::SHIPPED, 6, $now);

        $result = $this->simulator->canDeliverTimingOnly($poItem, $now);

        self::assertFalse($result);
    }

    #[WithStory(StaffUserStory::class)]
    public function testCanDeliverTimingOnlyReturnsTrueWhenAllConditionsMet(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $poItem = PurchaseOrderItemFactory::createOne(['supplier' => $supplier]);

        // Business hours (12 PM) with sufficient wait time (13 hours ago)
        $now = $this->createBusinessHoursTime(12);
        $this->createStatusChangeLogRelativeToNow($poItem, PurchaseOrderStatus::SHIPPED, 13, $now);

        $result = $this->simulator->canDeliverTimingOnly($poItem, $now);

        self::assertTrue($result);
    }

    #[WithStory(StaffUserStory::class)]
    public function testCanShipAtBusinessHoursBoundary(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $poItem = PurchaseOrderItemFactory::createOne(['supplier' => $supplier]);

        // Test at 9 AM (start of business hours)
        $now9am = $this->createBusinessHoursTime(9);
        $this->createStatusChangeLogRelativeToNow($poItem, PurchaseOrderStatus::ACCEPTED, 3, $now9am);
        $result = $this->simulator->canShipTimingOnly($poItem, $now9am);
        self::assertTrue($result);
    }

    #[WithStory(StaffUserStory::class)]
    public function testCanShipAtBusinessHoursBoundaryEnd(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $poItem = PurchaseOrderItemFactory::createOne(['supplier' => $supplier]);

        // Test at 17 PM (still within business hours)
        $now5pm = $this->createBusinessHoursTime(17);
        $this->createStatusChangeLogRelativeToNow($poItem, PurchaseOrderStatus::ACCEPTED, 3, $now5pm);
        $result = $this->simulator->canShipTimingOnly($poItem, $now5pm);
        self::assertTrue($result);
    }

    #[WithStory(StaffUserStory::class)]
    public function testCanShipAtBusinessHoursBoundaryFails(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $poItem = PurchaseOrderItemFactory::createOne(['supplier' => $supplier]);

        // Test at 18 PM (end boundary, should fail - hour < 18 required)
        $now6pm = $this->createBusinessHoursTime(18);
        $this->createStatusChangeLogRelativeToNow($poItem, PurchaseOrderStatus::ACCEPTED, 3, $now6pm);
        $result = $this->simulator->canShipTimingOnly($poItem, $now6pm);
        self::assertFalse($result);
    }

    #[WithStory(StaffUserStory::class)]
    public function testCanDeliverAtDeliveryHoursBoundary(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $poItem = PurchaseOrderItemFactory::createOne(['supplier' => $supplier]);

        // Test at 7 AM (start of delivery hours)
        $now7am = $this->createBusinessHoursTime(7);
        $this->createStatusChangeLogRelativeToNow($poItem, PurchaseOrderStatus::SHIPPED, 13, $now7am);
        $result = $this->simulator->canDeliverTimingOnly($poItem, $now7am);
        self::assertTrue($result);
    }

    #[WithStory(StaffUserStory::class)]
    public function testCanDeliverAtDeliveryHoursBoundaryEvening(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $poItem = PurchaseOrderItemFactory::createOne(['supplier' => $supplier]);

        // Test at 20 PM (still within delivery hours)
        $now8pm = $this->createBusinessHoursTime(20);
        $this->createStatusChangeLogRelativeToNow($poItem, PurchaseOrderStatus::SHIPPED, 13, $now8pm);
        $result = $this->simulator->canDeliverTimingOnly($poItem, $now8pm);
        self::assertTrue($result);
    }

    #[WithStory(StaffUserStory::class)]
    public function testCanDeliverAtDeliveryHoursBoundaryFails(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $poItem = PurchaseOrderItemFactory::createOne(['supplier' => $supplier]);

        // Test at 21 PM (end boundary, should fail - hour < 21 required)
        $now9pm = $this->createBusinessHoursTime(21);
        $this->createStatusChangeLogRelativeToNow($poItem, PurchaseOrderStatus::SHIPPED, 13, $now9pm);
        $result = $this->simulator->canDeliverTimingOnly($poItem, $now9pm);
        self::assertFalse($result);
    }

    private function createBusinessHoursTime(int $hour): \DateTimeImmutable
    {
        return new \DateTimeImmutable(sprintf('today %d:00:00', $hour));
    }

    /**
     * Creates a StatusChangeLog with an event timestamp relative to a "now" time.
     *
     * The timestamp is calculated as `$now - $hoursAgo hours`, so it's relative
     * to the provided "now" time rather than the actual current time.
     */
    private function createStatusChangeLogRelativeToNow(
        PurchaseOrderItem $poItem,
        PurchaseOrderStatus $status,
        int $hoursAgo,
        \DateTimeImmutable $now,
    ): StatusChangeLog {
        $eventTimestamp = $now->modify(sprintf('-%d hours', $hoursAgo));

        $poItemId = $poItem->getId();
        if ($poItemId === null) {
            throw new \RuntimeException('PurchaseOrderItem ID is null - ensure it is persisted before creating StatusChangeLog');
        }

        return StatusChangeLogFactory::createOne([
            'eventType' => DomainEventType::PURCHASE_ORDER_ITEM_STATUS_CHANGED,
            'eventTypeId' => $poItemId,
            'status' => $status->value,
            'eventTimestamp' => $eventTimestamp,
        ]);
    }
}
