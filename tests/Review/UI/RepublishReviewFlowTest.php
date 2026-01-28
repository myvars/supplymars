<?php

namespace App\Tests\Review\UI;

use App\Tests\Shared\Factory\ProductReviewFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class RepublishReviewFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testRepublishHiddenReview(): void
    {
        $review = ProductReviewFactory::new()->hidden()->create();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/review/' . $review->getPublicId()->value() . '/republish')
            ->assertOn('/review/' . $review->getPublicId()->value())
            ->assertSee('Review republished')
            ->assertSee('PUBLISHED');
    }
}
