<?php

namespace App\Tests\Catalog\UI;

use App\Tests\Shared\Factory\ProductImageFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class DeleteProductImageFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testDeleteViaFlow(): void
    {
        $productImage = ProductImageFactory::createOne();
        $publicId = $productImage->getPublicId()->value();
        $product = $productImage->getProduct();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/product/images/' . $publicId . '/remove')
            ->followRedirect()
            ->assertOn('/product_image/' . $product->getPublicId()->value() . '/images')
            ->assertSee('Product image deleted')
            ->assertSee('0 Product Images');
    }

    public function testDeleteNonExistentImage(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/product/images/999999/remove')
            ->assertStatus(500);
    }
}
