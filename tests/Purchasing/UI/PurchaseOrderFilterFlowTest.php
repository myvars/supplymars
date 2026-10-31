<?php

declare(strict_types=1);

namespace App\Tests\Purchasing\UI;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\UI\FilterRedirect;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Zenstruck\Browser\Test\HasBrowser;

final class PurchaseOrderFilterFlowTest extends WebTestCase
{
    use FilterRedirect;
    use HasBrowser;

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

        $this->assertRedirectedWithQuery($browser, '/purchase/order/', [
            'sort' => 'id',
            'sortDirection' => 'DESC',
            'page' => 1,
            'limit' => 5,
            'purchaseOrderId' => '123',
            'orderId' => $customerOrder->getId(),
            'customerId' => $customer->getId(),
            'productId' => $product->getId(),
            'supplierId' => $supplier->getId(),
            'purchaseOrderStatus' => strtolower(PurchaseOrderStatus::PENDING->value),
            'startDate' => '2025-01-01',
            'endDate' => '2025-12-31',
            'filter' => 'on',
        ]);
    }
}
