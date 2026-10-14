<?php

namespace App\Tests\Order\UI;

use App\Order\Domain\Model\Order\OrderStatus;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
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

        $values = [
            'order_filter[customerId]' => (string) $customer->getId(),
            'order_filter[productId]' => (string) $product->getId(),
            'order_filter[orderStatus]' => OrderStatus::PENDING->value,
            'order_filter[startDate]' => '2025-01-01',
            'order_filter[endDate]' => '2025-12-31',
        ];

        $browser = $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/search/filter')
            // The date pickers submit through hidden inputs, which fillField() cannot reach.
            ->assertSeeElement('input[type="hidden"][name="order_filter[startDate]"]')
            ->assertSeeElement('input[type="hidden"][name="order_filter[endDate]"]')
            ->use(static function (AbstractBrowser $client) use ($values): void {
                $client->submitForm('Apply Filter', $values);
            });

        $uri = $browser->client()->getRequest()->getUri();
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
