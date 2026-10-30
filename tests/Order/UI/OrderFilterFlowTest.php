<?php

declare(strict_types=1);

namespace App\Tests\Order\UI;

use App\Order\Domain\Model\Order\OrderStatus;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\UI\FilterRedirect;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Zenstruck\Browser\Test\HasBrowser;

final class OrderFilterFlowTest extends WebTestCase
{
    use FilterRedirect;
    use HasBrowser;

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

        $this->assertRedirectedWithQuery($browser, '/order/', [
            'sort' => 'id',
            'sortDirection' => 'DESC',
            'page' => 1,
            'limit' => 5,
            'customerId' => $customer->getId(),
            'productId' => $product->getId(),
            'orderStatus' => strtolower(OrderStatus::PENDING->value),
            'startDate' => '2025-01-01',
            'endDate' => '2025-12-31',
            'filter' => 'on',
        ]);
    }
}
