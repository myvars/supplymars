<?php

namespace App\Tests\Application\Controller;

use App\Factory\UserFactory;
use App\Factory\VatRateFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class VatRateControllerTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testIndexVatRate(): void
    {
        VatRateFactory::createMany(3);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/vat-rate/')
            ->assertSuccessful()
            ->assertSee('VAT Rate Search')
            ->assertSee('3 results');
    }

    public function testShowVatRate(): void
    {
        $vatRate = VatRateFactory::createone(['name' => 'VAT rate to be shown']);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/vat-rate/" . $vatRate->getId())
            ->assertSuccessful()
            ->assertSee('VAT rate to be shown');
    }

    public function testNewVatRate(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/vat-rate/new')
            ->assertSuccessful()
            ->fillField('vat_rate[name]','Test VAT rate')
            ->fillField('vat_rate[rate]','0.21')
            ->click('Create VAT Rate')
            ->assertOn('/vat-rate/')
            ->assertSee('Test VAT rate');
    }

    public function testNewVatRateValidation(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/vat-rate/new')
            ->assertSuccessful()
            // Intentionally omitting form data or filling it with invalid data
            ->click('Create VAT Rate')
            ->assertOn('/vat-rate/new')
            ->assertSee('Please enter a VAT rate name')
            ->assertSee('Please enter a VAT rate');
    }

    public function testEditVatRate(): void
    {
        $vatRate = VatRateFactory::createone(['name' => 'VAT rate to be edited']);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/vat-rate/" . $vatRate->getId() . "/edit")
            ->assertSuccessful()
            ->fillField('vat_rate[name]','Edited VAT rate')
            ->fillField('vat_rate[rate]','0.21')
            ->click('Update VAT Rate')
            ->assertOn('/vat-rate/')
            ->assertSee('Edited VAT rate');
    }

    public function testEditVatRateValidation(): void
    {
        $vatRate = VatRateFactory::createone(['name' => 'VAT rate to be edited']);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/vat-rate/" . $vatRate->getId() . "/edit")
            ->assertSuccessful()
            // Intentionally filling form with invalid data
            ->fillField('vat_rate[name]','')
            ->fillField('vat_rate[rate]','-1')
            ->click('Update VAT Rate')
            ->assertOn("/vat-rate/" . $vatRate->getId() . "/edit")
            ->assertSee('Please enter a VAT rate name')
            ->assertSee('Please enter a positive or zero VAT rate');
    }

    public function testDeleteVatRateConfirmation(): void
    {
        $vatRate = VatRateFactory::createone(['name' => 'VAT rate to be deleted']);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/vat-rate/" . $vatRate->getId() . "/delete/confirm")
            ->assertSuccessful()
            ->assertSee('Are you sure you want to delete this VAT rate');
    }

    public function testDeleteVatRate(): void
    {
        $vatRate = VatRateFactory::createone(['name' => 'VAT rate to be deleted']);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/vat-rate/" . $vatRate->getId() . "/delete/confirm")
            ->assertSuccessful()
            ->click('Delete')
            ->assertOn('/vat-rate/')
            ->assertNotSee('VAT rate to be deleted');
    }


    public function testVatRateNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/vat-rate/999")
            ->assertStatus(404);
    }
}