<?php

namespace App\Tests\Purchasing\UI;

use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class RemoveSupplierProductFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testSuccessfulRemove(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        $product = ProductFactory::createOne();
        $supplierProduct = SupplierProductFactory::createOne([
            'product' => $product,
        ]);

        $publicId = $supplierProduct->getPublicId()->value();

        $this->browser()
            ->actingAs($user)
            ->get('/supplier-product/'.$publicId.'/remove/confirm')
            ->click('Remove')
            ->assertStatus(200);

        self::assertNull($supplierProduct->getProduct());
    }

    public function testRemoveFailsWhenNotMapped(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        $supplierProduct = SupplierProductFactory::createOne([
            'product' => null,
        ]);

        $publicId = $supplierProduct->getPublicId()->value();

        $this->browser()
            ->actingAs($user)
            ->get('/supplier-product/'.$publicId.'/remove/confirm')
            ->click('Remove')
            ->assertStatus(500);
    }
}
