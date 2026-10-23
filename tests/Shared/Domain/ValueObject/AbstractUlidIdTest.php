<?php

declare(strict_types=1);

namespace App\Tests\Shared\Domain\ValueObject;

use App\Order\Domain\Model\Order\OrderPublicId;
use PHPUnit\Framework\TestCase;

final class AbstractUlidIdTest extends TestCase
{
    public function testTryFromStringReturnsIdForUlid(): void
    {
        $ulid = OrderPublicId::new()->value();

        self::assertSame($ulid, OrderPublicId::tryFromString($ulid)?->value());
    }

    public function testTryFromStringReturnsNullForMalformedValue(): void
    {
        self::assertNull(OrderPublicId::tryFromString('not-a-ulid'));
    }

    public function testFromStringThrowsForMalformedValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        OrderPublicId::fromString('not-a-ulid');
    }
}
