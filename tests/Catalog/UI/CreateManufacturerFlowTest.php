<?php

declare(strict_types=1);

namespace App\Tests\Catalog\UI;

use App\Tests\Shared\Factory\ManufacturerFactory;
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
            ->assertSuccessful()
            ->assertSee('Flow Manufacturer')
            ->assertNotOn('/manufacturer/');
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

    public function testRejectsOverLengthNameAsFieldError(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/manufacturer/new')
            ->fillField('manufacturer[name]', str_repeat('a', 256))
            ->checkField('manufacturer[isActive]')
            ->click('Create Manufacturer')
            ->assertStatus(422)
            ->assertSeeIn('[data-slot="field-error"]', 'Max 255 characters')
            ->assertNotSee('Object(');

        ManufacturerFactory::assert()->empty();
    }
}
