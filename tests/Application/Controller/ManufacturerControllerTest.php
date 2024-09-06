<?php

namespace App\Tests\Application\Controller;

use App\Factory\ManufacturerFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class ManufacturerControllerTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testIndexManufacturer(): void
    {
        ManufacturerFactory::createMany(3);

        $this->browser()
            ->get('/manufacturer/')
            ->assertSuccessful()
            ->assertSee('Manufacturer List')
            ->assertSee('3 results');
    }

    public function testShowManufacturer(): void
    {
        $manufacturer = ManufacturerFactory::createone(['name' => 'Manufacturer to be shown']);

        $this->browser()
            ->get("/manufacturer/" . $manufacturer->getId())
            ->assertSuccessful()
            ->assertSee('Manufacturer to be shown');
    }

    public function testNewManufacturer(): void
    {
        $this->browser()
            ->get('/manufacturer/new')
            ->assertSuccessful()
            ->fillField('manufacturer[name]','Test Manufacturer')
            ->fillField('manufacturer[isActive]','1')
            ->click('Create Manufacturer')
            ->assertOn('/manufacturer/')
            ->assertSee('Test Manufacturer');
    }

    public function testNewManufacturerValidation(): void
    {
        $this->browser()
            ->get('/manufacturer/new')
            ->assertSuccessful()
            // Intentionally omitting form data or filling it with invalid data
            ->click('Create Manufacturer')
            ->assertOn('/manufacturer/new')
            ->assertSee('Please enter a manufacturer name');
    }

    public function testEditManufacturer(): void
    {
        $manufacturer = ManufacturerFactory::createone(['name' => 'Manufacturer to be edited']);

        $this->browser()
            ->get("/manufacturer/" . $manufacturer->getId() . "/edit")
            ->assertSuccessful()
            ->fillField('manufacturer[name]','Edited Manufacturer')
            ->fillField('manufacturer[isActive]','1')
            ->click('Update Manufacturer')
            ->assertOn('/manufacturer/')
            ->assertSee('Edited Manufacturer');
    }

    public function testEditManufacturerValidation(): void
    {
        $manufacturer = ManufacturerFactory::createone(['name' => 'Manufacturer to be edited']);

        $this->browser()
            ->get("/manufacturer/" . $manufacturer->getId() . "/edit")
            ->assertSuccessful()
            // Intentionally filling form with invalid data
            ->fillField('manufacturer[name]','')
            ->click('Update Manufacturer')
            ->assertOn("/manufacturer/" . $manufacturer->getId() . "/edit")
            ->assertSee('Please enter a manufacturer name');
    }

    public function testDeleteManufacturerConfirmation(): void
    {
        $manufacturer = ManufacturerFactory::createone(['name' => 'Manufacturer to be deleted']);

        $this->browser()
            ->get("/manufacturer/" . $manufacturer->getId() . "/delete/confirm")
            ->assertSuccessful()
            ->assertSee('Are you sure you want to delete this Manufacturer');
    }

    public function testDeleteManufacturer(): void
    {
        $manufacturer = ManufacturerFactory::createone(['name' => 'Manufacturer to be deleted']);

        $this->browser()
            ->get("/manufacturer/" . $manufacturer->getId() . "/delete/confirm")
            ->assertSuccessful()
            ->click('Delete')
            ->assertOn('/manufacturer/')
            ->assertNotSee('Manufacturer to be deleted');
    }

    public function testManufacturerNotFound(): void
    {
        $this->browser()
            ->get("/manufacturer/999")
            ->assertSee("Sorry, we can't find that Manufacturer");
    }
}