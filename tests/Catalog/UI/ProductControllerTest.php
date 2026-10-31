<?php

declare(strict_types=1);

namespace App\Tests\Catalog\UI;

use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\ProductReviewFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class ProductControllerTest extends WebTestCase
{
    use HasBrowser;

    public function testReviewsShowsTheSummaryAndPublishedReviewsOnly(): void
    {
        $product = ProductFactory::createOne();
        ProductReviewFactory::new()->published()->create(['product' => $product, 'rating' => 4, 'title' => 'Survived the dust storm']);
        ProductReviewFactory::new()->published()->create(['product' => $product, 'rating' => 2, 'title' => 'Seals leak at night']);
        ProductReviewFactory::new()->hidden()->create(['product' => $product, 'rating' => 1, 'title' => 'Moderated away']);
        ProductReviewFactory::new()->published()->create(['title' => 'About another product']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/product/' . $product->getPublicId()->value() . '/reviews')
            ->assertSuccessful()
            ->assertSee('3.00')
            ->assertSee('2 reviews')
            ->assertSee('Survived the dust storm')
            ->assertSee('Seals leak at night')
            ->assertNotSee('Moderated away')
            ->assertNotSee('About another product');
    }

    public function testReviewsWithNoneShowsTheEmptyState(): void
    {
        $product = ProductFactory::createOne();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/product/' . $product->getPublicId()->value() . '/reviews')
            ->assertSuccessful()
            ->assertSee('No published reviews yet');
    }
}
