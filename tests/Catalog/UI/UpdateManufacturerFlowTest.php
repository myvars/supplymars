<?php

namespace App\Tests\Catalog\UI;

use App\Tests\Shared\Factory\ManufacturerFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class UpdateManufacturerFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testEditFormUpdatesAndRedirects(): void
    {
        $manufacturer = ManufacturerFactory::createOne(['name' => 'Before', 'isActive' => false]);
        $publicId = $manufacturer->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/manufacturer/'.$publicId.'/edit')
            ->fillField('manufacturer[name]', 'After')
            ->checkField('manufacturer[isActive]')
            ->click('Update Manufacturer')
            ->assertOn('/manufacturer/')
            ->assertSee('Manufacturer updated');
    }

    public function testEditFormValidationError(): void
    {
        $manufacturer = ManufacturerFactory::createOne();
        $publicId = $manufacturer->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/manufacturer/'.$publicId.'/edit')
            ->fillField('manufacturer[name]', '')
            ->click('Update Manufacturer')
            ->assertOn('/manufacturer/'.$publicId.'/edit')
            ->assertSee('Please enter a manufacturer name');
    }
}
