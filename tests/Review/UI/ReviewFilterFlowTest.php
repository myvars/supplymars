<?php

declare(strict_types=1);

namespace App\Tests\Review\UI;

use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class ReviewFilterFlowTest extends WebTestCase
{
    use HasBrowser;

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
