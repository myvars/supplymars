<?php

declare(strict_types=1);

namespace App\Tests\Review\UI;

use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\ProductReviewFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\UI\PostedRecordId;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class CreateReviewFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;
    use PostedRecordId;

    public function testFormRendersSuccessfully(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/review/new')
            ->assertSuccessful()
            ->assertSee('Create Review');
    }

    public function testValidationErrorsOnEmptySubmission(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/review/new')
            ->click('Create Review')
            ->assertOn('/review/new');
    }

    public function testIneligibleOrderIsRefusedOnTheField(): void
    {
        $customer = UserFactory::createOne();
        $product = ProductFactory::createOne();
        $order = CustomerOrderFactory::createOne(['customer' => $customer]);
        CustomerOrderItemFactory::createOne(['customerOrder' => $order, 'product' => $product]);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/review/new')
            ->fillField('Customer ID', (string) $customer->getId())
            ->fillField('Product ID', (string) $product->getId())
            ->fillField('Order ID', (string) $order->getId())
            ->selectFieldOption('Rating', '4 Stars')
            ->click('Create Review')
            ->assertStatus(422)
            ->assertSeeIn('[data-slot="field-error"]', 'Delivered product not found in this order.');

        ProductReviewFactory::assert()->count(0);
    }

    /**
     * A posted id used to make the form look like an edit, so the form skipped its eligibility check
     * (the handler still refused).
     */
    #[DataProvider('postedRecordIds')]
    public function testCreateRefusesAReviewIdPostedInTheForm(string $postedId): void
    {
        $other = ProductReviewFactory::createOne();
        $customer = UserFactory::createOne();
        $product = ProductFactory::createOne();
        $order = CustomerOrderFactory::createOne(['customer' => $customer]);
        CustomerOrderItemFactory::createOne(['customerOrder' => $order, 'product' => $product]);

        $this->assertRefusesPostedRecordId(
            $this->browser()->actingAs(UserFactory::new()->asStaff()->create()),
            '/review/new',
            'Create Review',
            'id',
            $postedId,
            $other->getPublicId()->value(),
            [
                'customerId' => (string) $customer->getId(),
                'productId' => (string) $product->getId(),
                'orderId' => (string) $order->getId(),
                'rating' => '4',
            ],
        );

        ProductReviewFactory::assert()->count(1);
    }
}
