<?php

namespace App\Tests\Review\UI;

use App\Tests\Shared\Factory\ProductReviewFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class HideReviewFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testHidePublishedReview(): void
    {
        $review = ProductReviewFactory::new()->published()->create();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/review/' . $review->getPublicId()->value() . '/hide')
            ->assertOn('/review/' . $review->getPublicId()->value())
            ->assertSee('Review hidden')
            ->assertSee('HIDDEN');
    }
}
