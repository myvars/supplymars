<?php

declare(strict_types=1);

namespace App\Tests\Purchasing\UI;

use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\PurchaseOrderFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class PurchaseOrderSortFlowTest extends WebTestCase
{
    use HasBrowser;

    public function testSortsByCustomerOrder(): void
    {
        $firstOrder = CustomerOrderFactory::createOne();
        $secondOrder = CustomerOrderFactory::createOne();
        // Created in the reverse order, so sorting by purchase order id gives the opposite result
        PurchaseOrderFactory::createOne(['customerOrder' => $secondOrder]);
        PurchaseOrderFactory::createOne(['customerOrder' => $firstOrder]);

        $content = $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/purchase/order/?sort=customerOrder.id&sortDirection=ASC')
            ->assertSuccessful()
            ->content();

        $first = strpos($content, 'Order #' . sprintf('%06d', $firstOrder->getId()));
        $second = strpos($content, 'Order #' . sprintf('%06d', $secondOrder->getId()));
        self::assertNotFalse($first);
        self::assertNotFalse($second);
        self::assertLessThan($second, $first);
    }
}
