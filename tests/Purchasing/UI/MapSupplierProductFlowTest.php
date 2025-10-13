<?php

namespace App\Tests\Purchasing\UI;

use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Factory\VatRateFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class MapSupplierProductFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    protected function setUp(): void
    {
        VatRateFactory::new()->withStandardRate()->create();
    }

    public function testSuccessfulMap(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        $supplierProduct = SupplierProductFactory::createOne([
            'product' => null,
        ]);

        $publicId = $supplierProduct->getPublicId()->value();

        $this->browser()
            ->actingAs($user)
            ->get('/supplier-product/'.$publicId.'/map')
            ->assertStatus(200) // redirect to show after success
            ->followRedirect()
            ->assertOn('/supplier-product/'.$supplierProduct->getPublicId())
            ->assertSee('Supplier product mapped');

        self::assertNotNull($supplierProduct->getProduct());
    }

    public function testMapFailsWhenAlreadyMapped(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        $supplierProduct = SupplierProductFactory::createOne();

        $publicId = $supplierProduct->getPublicId()->value();

        $this->browser()
            ->actingAs($user)
            ->get('/supplier-product/'.$publicId.'/map')
            ->assertStatus(200)
            ->followRedirect()
            ->assertOn('/supplier-product/'.$supplierProduct->getPublicId())
            ->assertSee('Supplier product already mapped');
    }
}
