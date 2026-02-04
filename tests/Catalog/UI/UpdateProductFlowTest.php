<?php

namespace App\Tests\Catalog\UI;

use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class UpdateProductFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testSuccessfulUpdateViaForm(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        $product = ProductFactory::new()->withActiveSource()->create();
        $publicId = $product->getPublicId()->value();

        $this->browser()
            ->actingAs($user)
            ->get('/product/' . $publicId . '/edit')
            ->fillField('product[name]', 'Flow Updated Product')
            ->fillField('product[mfrPartNumber]', 'FLOW-9876')
            ->fillField('product[isActive]', '1')
            ->click('Update Product')
            ->assertOn('/product/' . $publicId)
            ->assertSee('Flow Updated Product');
    }

    public function testValidationErrorsOnEmptySubmission(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        $product = ProductFactory::new()->withActiveSource()->create();
        $publicId = $product->getPublicId()->value();

        $this->browser()
            ->actingAs($user)
            ->get('/product/' . $publicId . '/edit')
            ->fillField('product[name]', '')
            ->fillField('product[mfrPartNumber]', '')
            ->click('Update Product')
            ->assertOn('/product/' . $publicId . '/edit')
            ->assertSee('Please enter a product name')
            ->assertSee('Please enter a manufacturer part number');
    }
}
