<?php

declare(strict_types=1);

namespace App\Tests\Catalog\UI;

use App\Tests\Shared\Factory\ManufacturerFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class DeleteManufacturerFlowTest extends WebTestCase
{
    use HasBrowser;

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

        ManufacturerFactory::assert()->notExists(['publicId' => $publicId]);
    }
}
