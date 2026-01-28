<?php

namespace App\Tests\Review\UI;

use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class CreateReviewFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testFormRendersSuccessfully(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/review/new')
            ->assertSuccessful()
            ->assertSee('Create Review');
    }

    public function testValidationErrorsOnEmptySubmission(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/review/new')
            ->click('Create Review')
            ->assertOn('/review/new');
    }
}
