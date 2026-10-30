<?php

declare(strict_types=1);

namespace App\Tests\Order\UI;

use App\Order\Domain\Model\Order\OrderStatus;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\UI\ActionFormToken;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class CancelOrderItemFlowTest extends WebTestCase
{
    use HasBrowser;
    use ActionFormToken;

    public function testCancelOrderItemViaFlow(): void
    {
        $order = CustomerOrderFactory::createOne();
        $orderItem = CustomerOrderItemFactory::createOne(['customerOrder' => $order, 'quantity' => 3]);
        $orderItemPublicId = $orderItem->getPublicId()->value();
        $orderPublicId = $order->getPublicId()->value();

        // A cancelled item has nothing outstanding, but stays in the order's item list
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/item/' . $orderItemPublicId . '/edit')
            ->click('Cancel Order Item')
            ->assertOn('/order/' . $orderPublicId)
            ->assertSee('Order item cancelled')
            ->assertSee($orderItem->getProduct()->getName())
            ->assertSee('Qty: 3')
            ->assertNotSee('Qty: 0 of 3');
    }

    public function testCancelIsNotReachableWithGet(): void
    {
        $orderItem = CustomerOrderItemFactory::createOne(['customerOrder' => CustomerOrderFactory::createOne()]);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/item/' . $orderItem->getPublicId()->value() . '/cancel')
            ->assertStatus(405);

        self::assertNotSame(OrderStatus::CANCELLED, CustomerOrderItemFactory::repository()->find($orderItem->getId())?->getStatus());
    }

    public function testCancelRejectsAMissingTokenOrAnotherItemsToken(): void
    {
        $order = CustomerOrderFactory::createOne();
        $other = CustomerOrderItemFactory::createOne(['customerOrder' => $order])->getPublicId()->value();
        $orderItem = CustomerOrderItemFactory::createOne(['customerOrder' => $order]);
        $url = '/order/item/' . $orderItem->getPublicId()->value() . '/cancel';
        $orderUrl = '/order/' . $order->getPublicId()->value();

        $browser = $this->browser()->actingAs(UserFactory::new()->asStaff()->create());
        $otherToken = $this->actionToken($browser, '/order/item/' . $other . '/edit', '/order/item/' . $other . '/cancel');

        $browser
            ->post($url)
            ->assertOn($orderUrl)
            ->assertSee('Invalid CSRF token.')
            ->post($url, ['body' => ['_token' => $otherToken]])
            ->assertOn($orderUrl)
            ->assertSee('Invalid CSRF token.');

        self::assertNotSame(OrderStatus::CANCELLED, CustomerOrderItemFactory::repository()->find($orderItem->getId())?->getStatus());
    }
}
