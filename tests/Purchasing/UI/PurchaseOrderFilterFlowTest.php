<?php

namespace App\Tests\Purchasing\UI;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class PurchaseOrderFilterFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testSubmitFilterFormRedirectsWithParams(): void
    {
        $supplier = SupplierFactory::createOne();
        $customer = UserFactory::createOne();
        $product = ProductFactory::createOne();
        $customerOrder = CustomerOrderFactory::createOne();

        $values = [
            'purchase_order_filter[supplier]' => (string) $supplier->getId(),
            'purchase_order_filter[purchaseOrderId]' => '123',
            'purchase_order_filter[orderId]' => (string) $customerOrder->getId(),
            'purchase_order_filter[customerId]' => (string) $customer->getId(),
            'purchase_order_filter[productId]' => (string) $product->getId(),
            'purchase_order_filter[purchaseOrderStatus]' => PurchaseOrderStatus::PENDING->value,
            'purchase_order_filter[startDate]' => '2025-01-01',
            'purchase_order_filter[endDate]' => '2025-12-31',
        ];

        $browser = $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/purchase/order/search/filter')
            // The date pickers submit through hidden inputs, which fillField() cannot reach.
            ->assertSeeElement('input[type="hidden"][name="purchase_order_filter[startDate]"]')
            ->assertSeeElement('input[type="hidden"][name="purchase_order_filter[endDate]"]')
            ->use(static function (AbstractBrowser $client) use ($values): void {
                $client->submitForm('Apply Filter', $values);
            });

        $uri = $browser->client()->getRequest()->getUri();
        $query = [];
        parse_str((string) parse_url((string) $uri, PHP_URL_QUERY), $query);

        // Assert base search params (defaults from PurchaseOrderSearchCriteria)
        self::assertSame('id', $query['sort']);
        self::assertSame('DESC', $query['sortDirection']);
        self::assertSame('1', $query['page']);
        self::assertSame('5', $query['limit']);

        // Assert filter params
        self::assertSame((string) $supplier->getId(), $query['supplierId']);
        self::assertSame('123', $query['purchaseOrderId']);
        self::assertSame((string) $customerOrder->getId(), $query['orderId']);
        self::assertSame((string) $customer->getId(), $query['customerId']);
        self::assertSame((string) $product->getId(), $query['productId']);
        self::assertSame(strtolower(PurchaseOrderStatus::PENDING->value), $query['purchaseOrderStatus']);
        self::assertSame('2025-01-01', $query['startDate']);
        self::assertSame('2025-12-31', $query['endDate']);
        self::assertSame('on', $query['filter']);
    }
}
