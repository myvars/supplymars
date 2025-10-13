<?php

namespace App\Tests\Pricing\UI;

use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class UpdateProductCostFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testSuccessfulEditViaForm(): void
    {
        $admin = UserFactory::new()->asStaff()->create();
        $product = ProductFactory::new()->withActiveSource()->create();
        $publicId = $product->getPublicId()->value();

        $this->browser()
            ->actingAs($admin)
            ->get('/pricing/'.$publicId.'/cost/product/edit')
            ->fillField('product_cost[defaultMarkup]', '7.500')
            ->fillField('product_cost[priceModel]', PriceModel::PRETTY_99->value)
            ->click('Update Pricing')
            ->assertOn('/pricing/'.$publicId.'/cost')
            ->assertSee('Product cost updated');
    }

    public function testValidationErrorsOnInvalidSubmission(): void
    {
        $admin = UserFactory::new()->asStaff()->create();
        $product = ProductFactory::new()->withActiveSource()->create();
        $publicId = $product->getPublicId()->value();

        $this->browser()
            ->actingAs($admin)
            ->get('/pricing/'.$publicId.'/cost/product/edit')
            ->fillField('product_cost[defaultMarkup]', '-1.000')
            ->click('Update')
            ->assertOn('/pricing/'.$publicId.'/cost/product/edit')
            ->assertSee('Please enter a positive or zero product markup %');
    }
}
