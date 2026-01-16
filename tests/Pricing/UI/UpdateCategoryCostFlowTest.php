<?php

namespace App\Tests\Pricing\UI;

use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class UpdateCategoryCostFlowTest extends WebTestCase
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
            ->get('/pricing/' . $publicId . '/cost/category/edit')
            ->fillField('category_cost[defaultMarkup]', '7.500')
            ->fillField('category_cost[priceModel]', PriceModel::PRETTY_99->value)
            ->click('Update Pricing')
            ->assertOn('/pricing/' . $publicId . '/cost')
            ->assertSee('Category cost updated');
    }

    public function testValidationErrorsOnInvalidSubmission(): void
    {
        $admin = UserFactory::new()->asStaff()->create();
        $product = ProductFactory::new()->withActiveSource()->create();
        $publicId = $product->getPublicId()->value();

        $this->browser()
            ->actingAs($admin)
            ->get('/pricing/' . $publicId . '/cost/category/edit')
            ->fillField('category_cost[defaultMarkup]', '-1.000')
            ->fillField('category_cost[priceModel]', PriceModel::NONE->value)
            ->click('Update')
            ->assertOn('/pricing/' . $publicId . '/cost/category/edit')
            ->assertSee('Please enter a positive or zero category markup %')
            ->assertSee('A category must have a price model');
    }
}
