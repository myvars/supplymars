<?php

namespace App\Tests\Catalog\UI;

use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class CreateManufacturerFlowTest extends WebTestCase
{
    use HasBrowser;

    public function testSuccessfulCreationViaForm(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/manufacturer/new')
            ->fillField('manufacturer[name]', 'Flow Manufacturer')
            ->checkField('manufacturer[isActive]')
            ->click('Create Manufacturer')
            ->assertOn('/manufacturer/')
            ->assertSee('Flow Manufacturer');
    }

    public function testValidationErrorsOnEmptySubmission(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/manufacturer/new')
            ->click('Create Manufacturer')
            ->assertOn('/manufacturer/new')
            ->assertSee('Please enter a manufacturer name');
    }
}
