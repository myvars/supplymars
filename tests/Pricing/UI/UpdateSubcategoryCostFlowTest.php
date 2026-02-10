<?php

namespace App\Tests\Pricing\UI;

use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class UpdateSubcategoryCostFlowTest extends WebTestCase
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
            ->get('/pricing/' . $publicId . '/cost/subcategory/edit')
            ->fillField('subcategory_cost[defaultMarkup]', '7.500')
            ->fillField('subcategory_cost[priceModel]', PriceModel::PRETTY_99->value)
            ->click('Update Subcategory Cost')
            ->assertOn('/pricing/' . $publicId . '/cost')
            ->assertSee('Subcategory cost updated');
    }

    public function testValidationErrorsOnInvalidSubmission(): void
    {
        $admin = UserFactory::new()->asStaff()->create();
        $product = ProductFactory::new()->withActiveSource()->create();
        $publicId = $product->getPublicId()->value();

        $this->browser()
            ->actingAs($admin)
            ->get('/pricing/' . $publicId . '/cost/subcategory/edit')
            ->fillField('subcategory_cost[defaultMarkup]', '-1.000')
            ->fillField('subcategory_cost[priceModel]', PriceModel::NONE->value)
            ->click('Update')
            ->assertOn('/pricing/' . $publicId . '/cost/subcategory/edit')
            ->assertSee('Please enter a positive or zero subcategory markup %');
    }
}
