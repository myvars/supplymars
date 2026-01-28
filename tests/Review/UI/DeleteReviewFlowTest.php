<?php

namespace App\Tests\Review\UI;

use App\Tests\Shared\Factory\ProductReviewFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class DeleteReviewFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testDeleteReviewWithConfirmation(): void
    {
        $review = ProductReviewFactory::createOne(['title' => 'Delete Me']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/review/' . $review->getPublicId()->value() . '/delete/confirm')
            ->assertSee('Confirm Delete')
            ->click('Delete Review')
            ->assertOn('/review/')
            ->assertSee('Review deleted');
    }
}
