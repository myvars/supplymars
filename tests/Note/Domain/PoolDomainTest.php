<?php

declare(strict_types=1);

namespace App\Tests\Note\Domain;

use App\Customer\Domain\Model\User\User;
use App\Note\Domain\Model\Pool\Pool;
use PHPUnit\Framework\TestCase;

final class PoolDomainTest extends TestCase
{
    public function testCreateTrimsTheNameAndDescription(): void
    {
        $pool = Pool::create('  Support  ', '  Help for customers  ', true, false);

        self::assertSame('Support', $pool->getName());
        self::assertSame('Help for customers', $pool->getDescription());
        self::assertTrue($pool->isActive());
        self::assertFalse($pool->isCustomerVisible());
    }

    public function testCreateFailsWithABlankName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Pool name cannot be empty');

        Pool::create('   ', null, true, true);
    }

    public function testUpdateFailsWithABlankNameAndKeepsTheOldOne(): void
    {
        $pool = Pool::create('Support', null, true, true);

        try {
            $pool->update('', null, false, false);
            self::fail('Expected a blank name to be refused');
        } catch (\InvalidArgumentException) {
        }

        self::assertSame('Support', $pool->getName());
        self::assertTrue($pool->isActive());
    }

    public function testSubscribeOnceAndUnsubscribe(): void
    {
        $pool = Pool::create('Support', null, true, true);
        $user = User::create('Sam Staff', 'sam@example.com', true, true);
        $other = User::create('Other Staff', 'other@example.com', true, true);

        $pool->subscribe($user);
        $pool->subscribe($user);

        self::assertTrue($pool->isSubscribedBy($user));
        self::assertFalse($pool->isSubscribedBy($other));
        self::assertCount(1, $pool->getSubscribers());

        $pool->unsubscribe($user);

        self::assertFalse($pool->isSubscribedBy($user));
        self::assertCount(0, $pool->getSubscribers());
    }
}
