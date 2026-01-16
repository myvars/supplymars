<?php

namespace App\Tests\Catalog\UI;

use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class DeleteProductFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testDeleteViaConfirmFlow(): void
    {
        $product = ProductFactory::createOne();
        $publicId = $product->getPublicId()->value();
        $user = UserFactory::new()->asStaff()->create();

        $this->browser()
            ->actingAs($user)
            ->get('/product/' . $publicId . '/delete/confirm')
            ->click('Delete Product')
            ->assertOn('/product/')
            ->assertSee('Product deleted');
    }
}
