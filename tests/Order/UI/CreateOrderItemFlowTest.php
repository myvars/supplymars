<?php

declare(strict_types=1);

namespace App\Tests\Order\UI;

use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\UI\PostedRecordId;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class CreateOrderItemFlowTest extends WebTestCase
{
    use HasBrowser;
    use PostedRecordId;

    #[DataProvider('postedRecordIds')]
    public function testCreateRefusesAnOrderIdPostedInTheForm(string $postedId): void
    {
        $other = CustomerOrderFactory::createOne();
        $order = CustomerOrderFactory::createOne();
        $product = ProductFactory::new()->withActiveSource()->create();

        $this->assertRefusesPostedRecordId(
            $this->browser()->actingAs(UserFactory::new()->asStaff()->create()),
            '/order/' . $order->getPublicId()->value() . '/item/new',
            'Create Order Item',
            'orderId',
            $postedId,
            $other->getPublicId()->value(),
            ['productId' => (string) $product->getId(), 'quantity' => '5'],
        );

        CustomerOrderItemFactory::assert()->count(0);
    }

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
            ->assertSuccessful()
            ->assertNotOn('/order/');
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

    public function testValidationErrorsOnNegativeProductId(): void
    {
        $order = CustomerOrderFactory::createOne();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/' . $order->getPublicId()->value() . '/item/new')
            ->fillField('order_item[productId]', '-1')
            ->fillField('order_item[quantity]', '5')
            ->click('Create Order Item')
            ->assertOn('/order/' . $order->getPublicId()->value() . '/item/new')
            ->assertSee('Please enter a valid product Id')
            ->assertSee('Product with Id "-1" not found');
    }
}
