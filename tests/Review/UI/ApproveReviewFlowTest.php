<?php

namespace App\Tests\Review\UI;

use App\Review\Domain\Model\Review\ReviewStatus;
use App\Tests\Shared\Factory\ProductReviewFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\UI\ActionFormToken;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class ApproveReviewFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;
    use ActionFormToken;

    public function testApproveChangesStatusToPublished(): void
    {
        $review = ProductReviewFactory::createOne();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/review/' . $review->getPublicId()->value())
            ->click('Approve')
            ->assertOn('/review/' . $review->getPublicId()->value())
            ->assertSee('Review approved')
            ->assertSee('PUBLISHED');
    }

    public function testApproveIsNotReachableWithGet(): void
    {
        $review = ProductReviewFactory::createOne();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/review/' . $review->getPublicId()->value() . '/approve')
            ->assertStatus(405);

        self::assertSame(ReviewStatus::PENDING, ProductReviewFactory::repository()->find($review->getId())?->getStatus());
    }

    public function testApproveRejectsAMissingTokenOrAnotherReviewsToken(): void
    {
        $other = ProductReviewFactory::createOne();
        $review = ProductReviewFactory::createOne();
        $url = '/review/' . $review->getPublicId()->value();

        $browser = $this->browser()->actingAs(UserFactory::new()->asStaff()->create());
        $otherToken = $this->actionToken($browser, '/review/' . $other->getPublicId()->value(), '/review/' . $other->getPublicId()->value() . '/approve');

        $browser
            ->post($url . '/approve')
            ->assertOn($url)
            ->assertSee('Invalid CSRF token.')
            ->post($url . '/approve', ['body' => ['_token' => $otherToken]])
            ->assertOn($url)
            ->assertSee('Invalid CSRF token.');

        self::assertSame(ReviewStatus::PENDING, ProductReviewFactory::repository()->find($review->getId())?->getStatus());
    }
}
