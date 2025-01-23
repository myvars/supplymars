<?php

namespace App\Tests\New\Unit\Entity;

use App\Entity\Category;
use App\Entity\StatusChangeLog;
use App\Entity\Subcategory;
use App\Entity\User;
use App\Enum\DomainEventType;
use App\Enum\PriceModel;
use PHPUnit\Framework\TestCase;

class StatusChangeLogTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $user = $this->createMock(User::class);
        $eventTimestamp = new \DateTimeImmutable();

        $statusChangeLog = new StatusChangeLog(
            DomainEventType::ORDER_STATUS_CHANGED,
            1,
            'SHIPPED',
            $user,
            $eventTimestamp
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
            $user,
            $eventTimestamp
        );

        $newUser = $this->createMock(User::class);
        $statusChangeLog->setUser($newUser);

        $this->assertSame($newUser, $statusChangeLog->getUser());
    }
}