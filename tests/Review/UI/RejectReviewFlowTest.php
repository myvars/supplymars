<?php

declare(strict_types=1);

namespace App\Tests\Review\UI;

use App\Review\Domain\Model\Review\ReviewStatus;
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

    public function testRejectsOverLengthNotesAsFieldError(): void
    {
        $review = ProductReviewFactory::createOne();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/review/' . $review->getPublicId()->value() . '/reject')
            ->selectFieldOption('reject_review[reason]', 'SPAM')
            ->fillField('reject_review[notes]', str_repeat('a', 10001))
            ->click('Reject Review')
            ->assertStatus(422)
            ->assertSeeIn('[data-slot="field-error"]', 'Max 10000 characters')
            ->assertNotSee('Object(');

        $fresh = ProductReviewFactory::repository()->find($review->getId());
        self::assertSame(ReviewStatus::PENDING, $fresh->getStatus());
    }
}
