<?php

namespace App\Tests\Review\UI;

use App\Review\Domain\Model\Review\ReviewStatus;
use App\Tests\Shared\Factory\ProductReviewFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\UI\ActionFormToken;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class RepublishReviewFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;
    use ActionFormToken;

    public function testRepublishHiddenReview(): void
    {
        $review = ProductReviewFactory::new()->hidden()->create();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/review/' . $review->getPublicId()->value())
            ->click('Republish')
            ->assertOn('/review/' . $review->getPublicId()->value())
            ->assertSee('Review republished')
            ->assertSee('PUBLISHED');
    }

    public function testRepublishIsNotReachableWithGet(): void
    {
        $review = ProductReviewFactory::new()->hidden()->create();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/review/' . $review->getPublicId()->value() . '/republish')
            ->assertStatus(405);

        self::assertSame(ReviewStatus::HIDDEN, ProductReviewFactory::repository()->find($review->getId())?->getStatus());
    }

    public function testRepublishRejectsAMissingTokenOrAnotherReviewsToken(): void
    {
        $other = ProductReviewFactory::new()->hidden()->create();
        $review = ProductReviewFactory::new()->hidden()->create();
        $url = '/review/' . $review->getPublicId()->value();

        $browser = $this->browser()->actingAs(UserFactory::new()->asStaff()->create());
        $otherToken = $this->actionToken($browser, '/review/' . $other->getPublicId()->value(), '/review/' . $other->getPublicId()->value() . '/republish');

        $browser
            ->post($url . '/republish')
            ->assertOn($url)
            ->assertSee('Invalid CSRF token.')
            ->post($url . '/republish', ['body' => ['_token' => $otherToken]])
            ->assertOn($url)
            ->assertSee('Invalid CSRF token.');

        self::assertSame(ReviewStatus::HIDDEN, ProductReviewFactory::repository()->find($review->getId())?->getStatus());
    }
}
