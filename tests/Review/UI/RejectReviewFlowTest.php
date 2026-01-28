<?php

namespace App\Tests\Review\UI;

use App\Tests\Shared\Factory\ProductReviewFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class RejectReviewFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testRejectWithReasonViaForm(): void
    {
        $review = ProductReviewFactory::createOne();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/review/' . $review->getPublicId()->value() . '/reject')
            ->selectFieldOption('reject_review[reason]', 'SPAM')
            ->fillField('reject_review[notes]', 'This is spam content')
            ->click('Reject Review')
            ->assertSee('Review rejected');
    }
}
