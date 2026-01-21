<?php

namespace App\Tests\Order\Application\Handler;

use App\Order\Application\Command\OrderFilter;
use App\Order\Application\Handler\OrderFilterHandler;
use App\Shared\Application\Search\FilterParamBuilder;
use PHPUnit\Framework\TestCase;

final class OrderFilterHandlerTest extends TestCase
{
    public function testBuildsRedirectWithExtraParams(): void
    {
        $params = new FilterParamBuilder();
        $handler = new OrderFilterHandler($params);

        $command = new OrderFilter(
            query: 'search term',
            sort: 'createdAt',
            sortDirection: 'DESC',
            page: 2,
            limit: 25,
            orderId: 123,
            purchaseOrderId: 456,
            customerId: 789,
            productId: 101,
            orderStatus: 'PENDING',
            startDate: '2025-01-01',
            endDate: '2025-12-31',
        );

        $result = $handler($command);

        self::assertTrue($result->ok);
        $redirect = $result->redirect;
        self::assertSame('app_order_index', $redirect->route);

        $built = $redirect->params;
        self::assertSame('search term', $built['query']);
        self::assertSame('createdAt', $built['sort']);
        self::assertSame('DESC', $built['sortDirection']);
        self::assertSame(2, $built['page']);
        self::assertSame(25, $built['limit']);
        self::assertSame(123, $built['orderId']);
        self::assertSame(456, $built['purchaseOrderId']);
        self::assertSame(789, $built['customerId']);
        self::assertSame(101, $built['productId']);
        self::assertSame('PENDING', $built['orderStatus']);
        self::assertSame('2025-01-01', $built['startDate']);
        self::assertSame('2025-12-31', $built['endDate']);
        self::assertSame('on', $built['filter']);
    }
}
