<?php

declare(strict_types=1);

namespace App\Tests\Purchasing\UI;

use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\UI\ActionFormToken;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Ulid;
use Zenstruck\Browser\Test\HasBrowser;

final class CreatePurchaseOrderItemFlowTest extends WebTestCase
{
    use HasBrowser;
    use ActionFormToken;

    public function testSuccessfulCreation(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
        ]);

        $order = CustomerOrderFactory::createOne();
        $orderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 5,
        ]);

        $orderPublicId = $order->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/' . $orderPublicId)
            ->click('form[action="' . $this->addUrl($orderItem->getPublicId()->value(), $supplierProduct->getPublicId()->value()) . '"] button')
            ->assertOn('/order/' . $orderPublicId)
            ->assertSee('Purchase order item updated');
    }

    public function testFailsWithInvalidOrderItem(): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isActive' => true]);
        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product,
            'stock' => 100,
        ]);

        $invalidOrderItemId = (string) new Ulid();
        $supplierProductPublicId = $supplierProduct->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->post($this->addUrl($invalidOrderItemId, $supplierProductPublicId))
            ->assertStatus(404);
    }

    public function testFailsWithInvalidSupplierProduct(): void
    {
        $product = ProductFactory::createOne(['isActive' => true]);
        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => SupplierFactory::createOne(['isActive' => true]),
            'product' => $product,
        ]);
        $order = CustomerOrderFactory::createOne();
        $orderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product,
            'quantity' => 5,
        ]);

        $orderItemPublicId = $orderItem->getPublicId()->value();
        $orderPublicId = $order->getPublicId()->value();

        $browser = $this->browser()->actingAs(UserFactory::new()->asStaff()->create());
        $token = $this->actionToken($browser, '/order/' . $orderPublicId, $this->addUrl($orderItemPublicId, $supplierProduct->getPublicId()->value()));

        $browser
            ->post($this->addUrl($orderItemPublicId, (string) new Ulid()), ['body' => ['_token' => $token]])
            ->assertOn('/order/' . $orderPublicId)
            ->assertSee('Supplier product not found');
    }

    public function testFailsWhenSupplierProductNotSourceForOrderItem(): void
    {
        // Two products, each with its own supplier product
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $product1 = ProductFactory::createOne(['isActive' => true]);
        $product2 = ProductFactory::createOne(['isActive' => true]);
        $source = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product1,
            'stock' => 100,
        ]);
        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => $product2,
            'stock' => 100,
        ]);

        // Create order item for product1
        $order = CustomerOrderFactory::createOne();
        $orderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => $order,
            'product' => $product1,
            'quantity' => 5,
        ]);

        $orderItemPublicId = $orderItem->getPublicId()->value();
        $orderPublicId = $order->getPublicId()->value();

        $browser = $this->browser()->actingAs(UserFactory::new()->asStaff()->create());
        $token = $this->actionToken($browser, '/order/' . $orderPublicId, $this->addUrl($orderItemPublicId, $source->getPublicId()->value()));

        // Post product2's supplier product against product1's order item
        $browser
            ->post($this->addUrl($orderItemPublicId, $supplierProduct->getPublicId()->value()), ['body' => ['_token' => $token]])
            ->assertOn('/order/' . $orderPublicId)
            ->assertSee('Supplier product source missing');
    }

    public function testAddIsNotReachableWithGet(): void
    {
        $product = ProductFactory::createOne(['isActive' => true]);
        $supplierProduct = SupplierProductFactory::createOne(['product' => $product, 'stock' => 100]);
        $orderItem = CustomerOrderItemFactory::createOne([
            'customerOrder' => CustomerOrderFactory::createOne(),
            'product' => $product,
        ]);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get($this->addUrl($orderItem->getPublicId()->value(), $supplierProduct->getPublicId()->value()))
            ->assertStatus(405);

        PurchaseOrderItemFactory::assert()->empty();
    }

    public function testAddRejectsAMissingTokenOrAnotherItemsToken(): void
    {
        $product = ProductFactory::createOne(['isActive' => true]);
        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => SupplierFactory::createOne(['isActive' => true]),
            'product' => $product,
            'stock' => 100,
        ]);
        $order = CustomerOrderFactory::createOne();
        $other = CustomerOrderItemFactory::createOne(['customerOrder' => $order, 'product' => $product, 'quantity' => 5]);
        $orderItem = CustomerOrderItemFactory::createOne(['customerOrder' => $order, 'product' => $product, 'quantity' => 5]);
        $orderUrl = '/order/' . $order->getPublicId()->value();
        $url = $this->addUrl($orderItem->getPublicId()->value(), $supplierProduct->getPublicId()->value());

        $browser = $this->browser()->actingAs(UserFactory::new()->asStaff()->create());
        $otherToken = $this->actionToken($browser, $orderUrl, $this->addUrl($other->getPublicId()->value(), $supplierProduct->getPublicId()->value()));

        $browser
            ->post($url)
            ->assertOn($orderUrl)
            ->assertSee('Invalid CSRF token.')
            ->post($url, ['body' => ['_token' => $otherToken]])
            ->assertOn($orderUrl)
            ->assertSee('Invalid CSRF token.');

        PurchaseOrderItemFactory::assert()->empty();
    }

    private function addUrl(string $orderItemId, string $supplierProductId): string
    {
        return '/order/item/' . $orderItemId . '/supplier/product/' . $supplierProductId . '/po/add';
    }
}
