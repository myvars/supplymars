<?php

declare(strict_types=1);

namespace App\Tests\Pricing\UI;

use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Factory\VatRateFactory;
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

    public function testRejectsOverLengthNameAsFieldError(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/vat-rate/new')
            ->fillField('vat_rate[name]', str_repeat('a', 256))
            ->fillField('vat_rate[rate]', '17.50')
            ->click('Create VAT Rate')
            ->assertStatus(422)
            ->assertSeeIn('[data-slot="field-error"]', 'Max 255 characters')
            ->assertNotSee('Object(');

        VatRateFactory::assert()->empty();
    }
}
