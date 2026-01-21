<?php

namespace App\Tests\Order\UI;

use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class CancelOrderItemFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testCancelOrderItemViaFlow(): void
    {
        $order = CustomerOrderFactory::createOne();
        $orderItem = CustomerOrderItemFactory::createOne(['customerOrder' => $order]);
        $orderItemPublicId = $orderItem->getPublicId()->value();
        $orderPublicId = $order->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/item/' . $orderItemPublicId . '/cancel')
            ->assertOn('/order/' . $orderPublicId)
            ->assertSee('Order item cancelled');
    }
}
