<?php

namespace App\Tests\Pricing\UI;

use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Factory\VatRateFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class UpdateVatRateFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testSuccessfulEditViaForm(): void
    {
        $vatRate = VatRateFactory::createOne();
        $publicId = $vatRate->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/vat-rate/' . $publicId . '/edit')
            ->fillField('vat_rate[name]', 'After Edit VAT')
            ->fillField('vat_rate[rate]', '7.25')
            ->click('Update VAT Rate')
            ->assertOn('/vat-rate/')
            ->assertSee('After Edit VAT');
    }

    public function testValidationErrorOnEmptyName(): void
    {
        $vatRate = VatRateFactory::createOne();
        $publicId = $vatRate->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/vat-rate/' . $publicId . '/edit')
            ->fillField('vat_rate[name]', '')
            ->click('Update VAT Rate')
            ->assertOn('/vat-rate/' . $publicId . '/edit')
            ->assertSee('Please enter a VAT rate name');
    }
}
