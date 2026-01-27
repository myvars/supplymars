<?php

namespace App\Tests\Reporting\UI\Http\Chart;

use App\Order\Domain\Model\Order\OrderStatus;
use App\Reporting\UI\Http\Chart\OrderStatusColorProvider;
use PHPUnit\Framework\TestCase;

class OrderStatusColorProviderTest extends TestCase
{
    private OrderStatusColorProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new OrderStatusColorProvider();
    }

    public function testGetColorReturnsValidColorForAllStatuses(): void
    {
        foreach (OrderStatus::cases() as $status) {
            $color = $this->provider->getColor($status->value);

            self::assertNotEmpty($color);
            self::assertStringStartsWith('#', $color);
        }
    }

    public function testGetSortOrderReturnsLevelForAllStatuses(): void
    {
        foreach (OrderStatus::cases() as $status) {
            $sortOrder = $this->provider->getSortOrder($status->value);

            self::assertSame($status->getLevel(), $sortOrder);
        }
    }
}
