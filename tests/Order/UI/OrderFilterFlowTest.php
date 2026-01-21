<?php

namespace App\Tests\Order\UI;

use App\Order\Domain\Model\Order\OrderStatus;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class OrderFilterFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testSubmitFilterFormRedirectsWithParams(): void
    {
        $customer = UserFactory::createOne();
        $product = ProductFactory::createOne();

        $browser = $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/search/filter')
            ->fillField('order_filter[customerId]', (string) $customer->getId())
            ->fillField('order_filter[productId]', (string) $product->getId())
            ->fillField('order_filter[orderStatus]', OrderStatus::PENDING->value)
            ->fillField('order_filter[startDate]', '2025-01-01')
            ->fillField('order_filter[endDate]', '2025-12-31')
            ->click('Apply Filter');

        $uri = $browser->crawler()->getUri();
        $query = [];
        parse_str((string) parse_url((string) $uri, PHP_URL_QUERY), $query);

        // Assert base search params (defaults from OrderSearchCriteria)
        self::assertSame('id', $query['sort']);
        self::assertSame('DESC', $query['sortDirection']);
        self::assertSame('1', $query['page']);
        self::assertSame('5', $query['limit']);

        // Assert filter params
        self::assertSame((string) $customer->getId(), $query['customerId']);
        self::assertSame((string) $product->getId(), $query['productId']);
        self::assertSame(strtolower(OrderStatus::PENDING->value), $query['orderStatus']);
        self::assertSame('2025-01-01', $query['startDate']);
        self::assertSame('2025-12-31', $query['endDate']);
        self::assertSame('on', $query['filter']);
    }
}
