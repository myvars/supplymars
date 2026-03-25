<?php

namespace App\Tests\Purchasing\UI;

use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class DeleteSupplierProductFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testDeleteViaConfirmFlow(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['name' => 'To Be Deleted', 'product' => null]);
        $publicId = $supplierProduct->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asSuperAdmin()->create())
            ->get('/supplier-product/' . $publicId . '/delete/confirm')
            ->click('Delete Supplier Product')
            ->assertOn('/supplier-product/')
            ->assertSee('Supplier Product deleted');
    }
}
