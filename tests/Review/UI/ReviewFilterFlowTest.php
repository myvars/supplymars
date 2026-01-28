<?php

namespace App\Tests\Review\UI;

use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class ReviewFilterFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testFilterFormRedirectsWithParams(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/review/search/filter')
            ->selectFieldOption('review_filter[reviewStatus]', 'PENDING')
            ->click('Apply Filter')
            ->assertSuccessful()
            ->assertOn('/review/', ['path']);
    }
}
