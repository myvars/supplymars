<?php

declare(strict_types=1);

namespace App\Tests\Review\UI;

use App\Tests\Shared\Factory\ProductReviewFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\UI\PostedRecordId;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class UpdateReviewFlowTest extends WebTestCase
{
    use HasBrowser;
    use PostedRecordId;

    public function testSuccessfulEditViaForm(): void
    {
        $review = ProductReviewFactory::createOne(['title' => 'Before Edit']);
        $publicId = $review->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/review/' . $publicId . '/edit')
            ->fillField('edit_review[title]', 'After Edit')
            ->click('Update Review')
            ->assertOn('/review/' . $publicId)
            ->assertSee('After Edit');
    }

    #[DataProvider('postedRecordIds')]
    public function testEditRefusesAReviewIdPostedInTheForm(string $postedId): void
    {
        $other = ProductReviewFactory::createOne(['title' => 'Other']);
        $review = ProductReviewFactory::createOne(['title' => 'Before Edit']);

        $this->assertRefusesPostedRecordId(
            $this->browser()->actingAs(UserFactory::new()->asStaff()->create()),
            '/review/' . $review->getPublicId()->value() . '/edit',
            'Update Review',
            'id',
            $postedId,
            $other->getPublicId()->value(),
            ['title' => 'Renamed'],
        );

        ProductReviewFactory::assert()->exists(['id' => $other->getId(), 'title' => 'Other']);
        ProductReviewFactory::assert()->exists(['id' => $review->getId(), 'title' => 'Before Edit']);
    }
}
