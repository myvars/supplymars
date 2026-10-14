<?php

namespace App\Tests\Review\UI;

use App\Review\Domain\Model\Review\ReviewStatus;
use App\Tests\Shared\Factory\ProductReviewFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\UI\ActionFormToken;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class HideReviewFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;
    use ActionFormToken;

    public function testHidePublishedReview(): void
    {
        $review = ProductReviewFactory::new()->published()->create();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/review/' . $review->getPublicId()->value())
            ->click('Hide')
            ->assertOn('/review/' . $review->getPublicId()->value())
            ->assertSee('Review hidden')
            ->assertSee('HIDDEN');
    }

    public function testHideIsNotReachableWithGet(): void
    {
        $review = ProductReviewFactory::new()->published()->create();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/review/' . $review->getPublicId()->value() . '/hide')
            ->assertStatus(405);

        self::assertSame(ReviewStatus::PUBLISHED, ProductReviewFactory::repository()->find($review->getId())?->getStatus());
    }

    public function testHideRejectsAMissingTokenOrAnotherReviewsToken(): void
    {
        $other = ProductReviewFactory::new()->published()->create();
        $review = ProductReviewFactory::new()->published()->create();
        $url = '/review/' . $review->getPublicId()->value();

        $browser = $this->browser()->actingAs(UserFactory::new()->asStaff()->create());
        $otherToken = $this->actionToken($browser, '/review/' . $other->getPublicId()->value(), '/review/' . $other->getPublicId()->value() . '/hide');

        $browser
            ->post($url . '/hide')
            ->assertOn($url)
            ->assertSee('Invalid CSRF token.')
            ->post($url . '/hide', ['body' => ['_token' => $otherToken]])
            ->assertOn($url)
            ->assertSee('Invalid CSRF token.');

        self::assertSame(ReviewStatus::PUBLISHED, ProductReviewFactory::repository()->find($review->getId())?->getStatus());
    }
}
