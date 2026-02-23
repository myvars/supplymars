<?php

namespace App\Tests\Order\UI\Api;

use App\Tests\Shared\Factory\AddressFactory;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Factory\VatRateFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\HttpOptions;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class OrderApiTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testOrderIndexRequiresAuthentication(): void
    {
        $this->browser()
            ->get('/api/v1/orders')
            ->assertStatus(401);
    }

    public function testOrderIndexReturnsCollectionWithLinks(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        CustomerOrderFactory::createMany(3);

        $this->browser()
            ->actingAs($user, 'api')
            ->get('/api/v1/orders')
            ->assertSuccessful()
            ->assertJson()
            ->assertJsonMatches('length(data)', 3)
            ->assertJsonMatches('"meta"."page"', 1)
            ->assertJsonMatches('"meta"."total"', 3)
            ->assertJsonMatches('"links"."self" != null', true);
    }

    public function testOrderShowReturnsItem(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        $order = CustomerOrderFactory::createOne();

        $this->browser()
            ->actingAs($user, 'api')
            ->get('/api/v1/orders/' . $order->getPublicId()->value())
            ->assertSuccessful()
            ->assertJson()
            ->assertJsonMatches('"data"."id"', $order->getPublicId()->value());
    }

    public function testCreateOrderReturns201(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        $customer = UserFactory::createOne();
        AddressFactory::createOne([
            'customer' => $customer,
            'isDefaultBillingAddress' => true,
            'isDefaultShippingAddress' => true,
        ]);
        VatRateFactory::new()->withStandardRate()->create();

        $this->browser()
            ->actingAs($user, 'api')
            ->post('/api/v1/orders', HttpOptions::json([
                'customer' => $customer->getPublicId()->value(),
                'shippingMethod' => 'THREE_DAY',
                'customerOrderRef' => 'TEST-001',
            ]))
            ->assertStatus(201)
            ->assertJson()
            ->assertJsonMatches('"data"."id" != null', true)
            ->assertJsonMatches('"data"."customerOrderRef"', 'TEST-001');
    }

    public function testCreateOrderWithInvalidCustomerReturns422(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        VatRateFactory::new()->withStandardRate()->create();

        $this->browser()
            ->actingAs($user, 'api')
            ->post('/api/v1/orders', HttpOptions::json([
                'customer' => '01ZZZZZZZZZZZZZZZZZZZZZZZZ',
                'shippingMethod' => 'THREE_DAY',
            ]))
            ->assertStatus(422)
            ->assertJson()
            ->assertJsonMatches('type', 'about:blank')
            ->assertJsonMatches('detail', 'Customer not found.');
    }

    public function testCancelNonExistentOrderReturns404(): void
    {
        $user = UserFactory::new()->asStaff()->create();

        $this->browser()
            ->actingAs($user, 'api')
            ->post('/api/v1/orders/01ZZZZZZZZZZZZZZZZZZZZZZZZ/cancel')
            ->assertStatus(404)
            ->assertJson()
            ->assertJsonMatches('type', 'about:blank')
            ->assertJsonMatches('status', 404);
    }

    public function testAddOrderItemReturns201(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        $order = CustomerOrderFactory::createOne();
        $product = ProductFactory::new()->withActiveSource()->create();

        $this->browser()
            ->actingAs($user, 'api')
            ->post('/api/v1/orders/' . $order->getPublicId()->value() . '/items', HttpOptions::json([
                'product' => $product->getPublicId()->value(),
                'quantity' => 5,
            ]))
            ->assertStatus(201)
            ->assertJson()
            ->assertJsonMatches('"data"."quantity"', 5);
    }

    public function testAddOrderItemWithInvalidProductReturns422(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        $order = CustomerOrderFactory::createOne();

        $this->browser()
            ->actingAs($user, 'api')
            ->post('/api/v1/orders/' . $order->getPublicId()->value() . '/items', HttpOptions::json([
                'product' => '01ZZZZZZZZZZZZZZZZZZZZZZZZ',
                'quantity' => 5,
            ]))
            ->assertStatus(422)
            ->assertJson()
            ->assertJsonMatches('detail', 'Product not found.');
    }

    public function testOrderNotFoundReturns404(): void
    {
        $user = UserFactory::new()->asStaff()->create();

        $this->browser()
            ->actingAs($user, 'api')
            ->get('/api/v1/orders/01ZZZZZZZZZZZZZZZZZZZZZZZZ')
            ->assertStatus(404)
            ->assertJsonMatches('type', 'about:blank')
            ->assertJsonMatches('status', 404);
    }
}
