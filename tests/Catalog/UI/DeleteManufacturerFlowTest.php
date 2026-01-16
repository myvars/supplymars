<?php

namespace App\Tests\Catalog\UI;

use App\Tests\Shared\Factory\ManufacturerFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class DeleteManufacturerFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testDeleteFromConfirmRemovesAndRedirects(): void
    {
        $manufacturer = ManufacturerFactory::createOne();
        $publicId = $manufacturer->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/manufacturer/' . $publicId . '/delete/confirm')
            ->click('Delete')
            ->assertOn('/manufacturer/')
            ->assertSee('Manufacturer deleted');
    }
}
