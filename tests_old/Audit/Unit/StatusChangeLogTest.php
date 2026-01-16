<?php

namespace App\Tests\Audit\Unit;

use App\Audit\Domain\Model\StatusChange\StatusChangeLog;
use App\Customer\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventType;
use PHPUnit\Framework\TestCase;

class StatusChangeLogTest extends TestCase
{
    public function testCreate(): void
    {
        $user = $this->createMock(User::class);
        $eventTimestamp = new \DateTimeImmutable();

        $statusChangeLog = new StatusChangeLog(
            DomainEventType::ORDER_STATUS_CHANGED,
            1,
            'SHIPPED',
            $eventTimestamp,
            $user
        );

        $this->assertSame(DomainEventType::ORDER_STATUS_CHANGED, $statusChangeLog->getEventType());
        $this->assertEquals(1, $statusChangeLog->getEventTypeId());
        $this->assertEquals('SHIPPED', $statusChangeLog->getStatus());
        $this->assertSame($user, $statusChangeLog->getUser());
        $this->assertSame($eventTimestamp, $statusChangeLog->getEventTimestamp());
    }

    public function testSetUser(): void
    {
        $user = $this->createMock(User::class);
        $eventTimestamp = new \DateTimeImmutable();

        $statusChangeLog = new StatusChangeLog(
            DomainEventType::ORDER_STATUS_CHANGED,
            1,
            'SHIPPED',
            $eventTimestamp,
            $user
        );

        $newUser = $this->createMock(User::class);
        $statusChangeLog->assignUser($newUser);

        $this->assertSame($newUser, $statusChangeLog->getUser());
    }
}
