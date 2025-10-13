<?php

namespace App\Tests\Pricing\UI;

use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class CreateVatRateFlowTest extends WebTestCase
{
    use HasBrowser;

    public function testSuccessfulCreationViaForm(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/vat-rate/new')
            ->fillField('vat_rate[name]', 'Flow VAT')
            ->fillField('vat_rate[rate]', '17.50')
            ->click('Create VAT Rate')
            ->assertOn('/vat-rate/')
            ->assertSee('Flow VAT');
    }

    public function testValidationErrorsOnEmptySubmission(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/vat-rate/new')
            ->click('Create VAT Rate')
            ->assertOn('/vat-rate/new')
            ->assertSee('Please enter a VAT rate name')
            ->assertSee('Please enter a VAT rate');
    }
}
