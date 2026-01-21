<?php

namespace App\Tests\Order\UI;

use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class CreateOrderItemFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testSuccessfulCreationViaForm(): void
    {
        $order = CustomerOrderFactory::createOne();
        $product = ProductFactory::new()->withActiveSource()->create();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/' . $order->getPublicId()->value() . '/item/new')
            ->fillField('order_item[productId]', (string) $product->getId())
            ->fillField('order_item[quantity]', '5')
            ->click('Create Order Item')
            ->assertOn('/order/');
    }

    public function testValidationErrorsOnEmptySubmission(): void
    {
        $order = CustomerOrderFactory::createOne();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/' . $order->getPublicId()->value() . '/item/new')
            ->click('Create Order Item')
            ->assertOn('/order/' . $order->getPublicId()->value() . '/item/new')
            ->assertSee('Please enter a product Id')
            ->assertSee('Please enter a quantity');
    }

    public function testValidationErrorsOnInvalidProductId(): void
    {
        $order = CustomerOrderFactory::createOne();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/' . $order->getPublicId()->value() . '/item/new')
            ->fillField('order_item[productId]', '999999')
            ->fillField('order_item[quantity]', '5')
            ->click('Create Order Item')
            ->assertOn('/order/' . $order->getPublicId()->value() . '/item/new')
            ->assertSee('Product with Id "999999" not found');
    }
}
