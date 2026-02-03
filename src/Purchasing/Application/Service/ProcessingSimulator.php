<?php

namespace App\Purchasing\Application\Service;

use App\Audit\Domain\Model\StatusChange\StatusChangeLog;
use App\Audit\Domain\Repository\StatusChangeLogRepository;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;

/**
 * Simulates real-world timing constraints for shipping and delivery operations.
 *
 * Used by console commands to simulate realistic processing delays:
 * - Shipping: Business hours 9-18, 2+ hours since acceptance, 95% probability
 * - Delivery: Business hours 7-21, 12+ hours since shipping, 95% probability
 */
final readonly class ProcessingSimulator
{
    private const int SHIPPING_START_HOUR = 9;

    private const int SHIPPING_END_HOUR = 18;

    private const string SHIPPING_MINIMUM_WAIT = '2 hours';

    private const int SHIPPING_PROBABILITY = 20; // 1 in 20 fails (95% success)

    private const int DELIVERY_START_HOUR = 7;

    private const int DELIVERY_END_HOUR = 21;

    private const string DELIVERY_MINIMUM_WAIT = '12 hours';

    private const int DELIVERY_PROBABILITY = 20; // 1 in 20 fails (95% success)

    public function __construct(
        private StatusChangeLogRepository $statusChangeLogs,
    ) {
    }

    /**
     * Determines if a purchase order item can be shipped.
     *
     * Requirements:
     * - Current time within business hours (9:00-18:00)
     * - At least 2 hours since acceptance
     * - 95% probability of success
     */
    public function canShip(PurchaseOrderItem $purchaseOrderItem, ?\DateTimeImmutable $now = null): bool
    {
        $statusChangeLog = $this->getStatusChangeLog($purchaseOrderItem, PurchaseOrderStatus::ACCEPTED);
        if (!$statusChangeLog instanceof StatusChangeLog) {
            return false;
        }

        $now ??= new \DateTimeImmutable();

        if (!$this->isWithinBusinessHours($now, self::SHIPPING_START_HOUR, self::SHIPPING_END_HOUR)) {
            return false;
        }

        if (!$this->hasElapsedMinimumWait($statusChangeLog->getEventTimestamp(), $now, self::SHIPPING_MINIMUM_WAIT)) {
            return false;
        }

        return $this->passesRandomCheck(self::SHIPPING_PROBABILITY);
    }

    /**
     * Determines if a purchase order item can be delivered.
     *
     * Requirements:
     * - Current time within business hours (7:00-21:00)
     * - At least 12 hours since shipping
     * - 95% probability of success
     */
    public function canDeliver(PurchaseOrderItem $purchaseOrderItem, ?\DateTimeImmutable $now = null): bool
    {
        $statusChangeLog = $this->getStatusChangeLog($purchaseOrderItem, PurchaseOrderStatus::SHIPPED);
        if (!$statusChangeLog instanceof StatusChangeLog) {
            return false;
        }

        $now ??= new \DateTimeImmutable();

        if (!$this->isWithinBusinessHours($now, self::DELIVERY_START_HOUR, self::DELIVERY_END_HOUR)) {
            return false;
        }

        if (!$this->hasElapsedMinimumWait($statusChangeLog->getEventTimestamp(), $now, self::DELIVERY_MINIMUM_WAIT)) {
            return false;
        }

        return $this->passesRandomCheck(self::DELIVERY_PROBABILITY);
    }

    /**
     * Checks if shipping is allowed based on timing only (no probability check).
     * Useful for testing to verify timing constraints.
     */
    public function canShipTimingOnly(PurchaseOrderItem $purchaseOrderItem, ?\DateTimeImmutable $now = null): bool
    {
        $statusChangeLog = $this->getStatusChangeLog($purchaseOrderItem, PurchaseOrderStatus::ACCEPTED);
        if (!$statusChangeLog instanceof StatusChangeLog) {
            return false;
        }

        $now ??= new \DateTimeImmutable();

        if (!$this->isWithinBusinessHours($now, self::SHIPPING_START_HOUR, self::SHIPPING_END_HOUR)) {
            return false;
        }

        return $this->hasElapsedMinimumWait($statusChangeLog->getEventTimestamp(), $now, self::SHIPPING_MINIMUM_WAIT);
    }

    /**
     * Checks if delivery is allowed based on timing only (no probability check).
     * Useful for testing to verify timing constraints.
     */
    public function canDeliverTimingOnly(PurchaseOrderItem $purchaseOrderItem, ?\DateTimeImmutable $now = null): bool
    {
        $statusChangeLog = $this->getStatusChangeLog($purchaseOrderItem, PurchaseOrderStatus::SHIPPED);
        if (!$statusChangeLog instanceof StatusChangeLog) {
            return false;
        }

        $now ??= new \DateTimeImmutable();

        if (!$this->isWithinBusinessHours($now, self::DELIVERY_START_HOUR, self::DELIVERY_END_HOUR)) {
            return false;
        }

        return $this->hasElapsedMinimumWait($statusChangeLog->getEventTimestamp(), $now, self::DELIVERY_MINIMUM_WAIT);
    }

    private function getStatusChangeLog(PurchaseOrderItem $purchaseOrderItem, PurchaseOrderStatus $status): ?StatusChangeLog
    {
        return $this->statusChangeLogs->findPoStatusChangeByStatus(
            $purchaseOrderItem->getId(),
            $status
        );
    }

    private function isWithinBusinessHours(\DateTimeImmutable $now, int $startHour, int $endHour): bool
    {
        $hour = (int) $now->format('G');

        return $hour >= $startHour && $hour < $endHour;
    }

    private function hasElapsedMinimumWait(\DateTimeImmutable $eventTime, \DateTimeImmutable $now, string $minimumWait): bool
    {
        $interval = \DateInterval::createFromDateString($minimumWait);

        if ($interval === false) {
            throw new \InvalidArgumentException(sprintf('Invalid interval string: %s', $minimumWait));
        }

        $minimumTime = $now->sub($interval);

        return $eventTime <= $minimumTime;
    }

    private function passesRandomCheck(int $denominator): bool
    {
        return 0 !== random_int(0, $denominator - 1);
    }
}
