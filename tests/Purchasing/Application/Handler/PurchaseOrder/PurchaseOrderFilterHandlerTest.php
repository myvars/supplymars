<?php

namespace App\Tests\Purchasing\Application\Handler\PurchaseOrder;

use App\Purchasing\Application\Command\PurchaseOrder\PurchaseOrderFilter;
use App\Purchasing\Application\Handler\PurchaseOrder\PurchaseOrderFilterHandler;
use App\Shared\Application\Search\FilterParamBuilder;
use PHPUnit\Framework\TestCase;

final class PurchaseOrderFilterHandlerTest extends TestCase
{
    public function testBuildsRedirectWithExtraParams(): void
    {
        $params = new FilterParamBuilder();
        $handler = new PurchaseOrderFilterHandler($params);

        $command = new PurchaseOrderFilter(
            query: 'search term',
            sort: 'createdAt',
            sortDirection: 'DESC',
            page: 2,
            limit: 25,
            purchaseOrderId: 123,
            orderId: 456,
            customerId: 789,
            productId: 101,
            supplierId: 202,
            purchaseOrderStatus: 'PENDING',
            startDate: '2025-01-01',
            endDate: '2025-12-31',
        );

        $result = $handler($command);

        self::assertTrue($result->ok);
        $redirect = $result->redirect;
        self::assertSame('app_purchasing_purchase_order_index', $redirect->route);

        $built = $redirect->params;
        self::assertSame('search term', $built['query']);
        self::assertSame('createdAt', $built['sort']);
        self::assertSame('DESC', $built['sortDirection']);
        self::assertSame(2, $built['page']);
        self::assertSame(25, $built['limit']);
        self::assertSame(123, $built['purchaseOrderId']);
        self::assertSame(456, $built['orderId']);
        self::assertSame(789, $built['customerId']);
        self::assertSame(101, $built['productId']);
        self::assertSame(202, $built['supplierId']);
        self::assertSame('PENDING', $built['purchaseOrderStatus']);
        self::assertSame('2025-01-01', $built['startDate']);
        self::assertSame('2025-12-31', $built['endDate']);
        self::assertSame('on', $built['filter']);
    }
}
