<?php

namespace App\Tests\Audit\Domain;

use App\Audit\Domain\Model\StatusChange\StatusChangeLog;
use App\Customer\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventType;
use PHPUnit\Framework\TestCase;

final class StatusChangeDomainTest extends TestCase
{
    public function testCreatesWithValidParameters(): void
    {
        $user = $this->createStub(User::class);
        $timestamp = new \DateTimeImmutable('2024-01-01T00:00:00Z');

        $log = new StatusChangeLog(
            eventType: DomainEventType::ORDER_STATUS_CHANGED,
            eventTypeId: 42,
            status: 'processing',
            eventTimestamp: $timestamp,
            user: $user,
        );

        self::assertSame(DomainEventType::ORDER_STATUS_CHANGED, $log->getEventType());
        self::assertSame(42, $log->getEventTypeId());
        self::assertSame('processing', $log->getStatus());
    }

    public function testDifferentEventTypes(): void
    {
        $user = $this->createStub(User::class);
        $timestamp = new \DateTimeImmutable('2024-01-01T00:00:00Z');

        $statusEventTypes = [
            DomainEventType::ORDER_STATUS_CHANGED,
            DomainEventType::ORDER_ITEM_STATUS_CHANGED,
            DomainEventType::PURCHASE_ORDER_STATUS_CHANGED,
            DomainEventType::PURCHASE_ORDER_ITEM_STATUS_CHANGED,
        ];

        foreach ($statusEventTypes as $eventType) {
            $log = new StatusChangeLog(
                eventType: $eventType,
                eventTypeId: 1,
                status: 'completed',
                eventTimestamp: $timestamp,
                user: $user,
            );

            self::assertSame($eventType, $log->getEventType());
        }
    }

    public function testEventTimestampIsPreserved(): void
    {
        $user = $this->createStub(User::class);
        $timestamp = new \DateTimeImmutable('2024-03-15T14:30:00Z');

        $log = new StatusChangeLog(
            eventType: DomainEventType::ORDER_STATUS_CHANGED,
            eventTypeId: 1,
            status: 'pending',
            eventTimestamp: $timestamp,
            user: $user,
        );

        self::assertSame($timestamp, $log->getEventTimestamp());
    }

    public function testIdIsNullBeforePersistence(): void
    {
        $user = $this->createStub(User::class);

        $log = new StatusChangeLog(
            eventType: DomainEventType::ORDER_STATUS_CHANGED,
            eventTypeId: 1,
            status: 'pending',
            eventTimestamp: new \DateTimeImmutable(),
            user: $user,
        );

        self::assertNull($log->getId());
    }

    public function testUserIsAssigned(): void
    {
        $user = $this->createStub(User::class);

        $log = new StatusChangeLog(
            eventType: DomainEventType::ORDER_STATUS_CHANGED,
            eventTypeId: 1,
            status: 'pending',
            eventTimestamp: new \DateTimeImmutable(),
            user: $user,
        );

        self::assertSame($user, $log->getUser());
    }
}
