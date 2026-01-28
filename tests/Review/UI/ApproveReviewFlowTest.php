<?php

namespace App\Tests\Review\UI;

use App\Tests\Shared\Factory\ProductReviewFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class ApproveReviewFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testApproveChangesStatusToPublished(): void
    {
        $review = ProductReviewFactory::createOne();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/review/' . $review->getPublicId()->value() . '/approve')
            ->assertOn('/review/' . $review->getPublicId()->value())
            ->assertSee('Review approved')
            ->assertSee('PUBLISHED');
    }
}
