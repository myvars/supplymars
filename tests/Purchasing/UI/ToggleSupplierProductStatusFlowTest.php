<?php

namespace App\Tests\Purchasing\UI;

use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class ToggleSupplierProductStatusFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testSuccessfulToggle(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        $supplierProduct = SupplierProductFactory::createOne([
            'isActive' => true,
            'product' => ProductFactory::createOne(),
        ]);

        $publicId = $supplierProduct->getPublicId()->value();

        $this->browser()
            ->actingAs($user)
            ->get('/supplier-product/'.$publicId.'/status/toggle')
            ->assertStatus(200);

        self::assertFalse($supplierProduct->isActive());

        $this->browser()
            ->actingAs($user)
            ->get('/supplier-product/'.$publicId.'/status/toggle')
            ->assertStatus(200);

        self::assertTrue($supplierProduct->isActive());
    }

    public function testToggleFailsWhenNotMapped(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        $supplierProduct = SupplierProductFactory::createOne([
            'isActive' => true,
            'product' => null,
        ]);

        $publicId = $supplierProduct->getPublicId()->value();

        $this->browser()
            ->actingAs($user)
            ->get('/supplier-product/'.$publicId.'/status/toggle')
            ->assertStatus(500) ;
    }
}
