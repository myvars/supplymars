<?php

declare(strict_types=1);

namespace App\Tests\Pricing\UI;

use App\Catalog\Domain\Model\Product\Product;
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
            ->click('Update Category Cost')
            ->assertOn('/pricing/' . $publicId . '/cost')
            ->assertSee('Category cost updated');
    }

    public function testEditReachingNeverSourcedProductKeepsItsPrice(): void
    {
        $admin = UserFactory::new()->asStaff()->create();
        $product = ProductFactory::createOne();
        $publicId = $product->getPublicId()->value();

        $this->browser()
            ->actingAs($admin)
            ->get('/pricing/' . $publicId . '/cost/category/edit')
            ->fillField('category_cost[defaultMarkup]', '7.500')
            ->fillField('category_cost[priceModel]', PriceModel::PRETTY_99->value)
            ->click('Update Category Cost')
            ->assertOn('/pricing/' . $publicId . '/cost')
            ->assertSee('Category cost updated');

        $reloaded = ProductFactory::repository()->find($product->getId());
        self::assertInstanceOf(Product::class, $reloaded);
        self::assertSame('0.00', $reloaded->getCost());
        self::assertSame('0.00', $reloaded->getSellPriceIncVat());
    }

    public function testZeroMarkupPricesAtCost(): void
    {
        $admin = UserFactory::new()->asStaff()->create();
        $product = ProductFactory::new()->withActiveSource()->create();
        $publicId = $product->getPublicId()->value();

        $this->browser()
            ->actingAs($admin)
            ->get('/pricing/' . $publicId . '/cost/category/edit')
            ->fillField('category_cost[defaultMarkup]', '0.000')
            ->fillField('category_cost[priceModel]', PriceModel::DEFAULT->value)
            ->click('Update Category Cost')
            ->assertOn('/pricing/' . $publicId . '/cost')
            ->assertSee('Category cost updated');

        $reloaded = ProductFactory::repository()->find($product->getId());
        self::assertInstanceOf(Product::class, $reloaded);
        self::assertSame('0.000', $reloaded->getMarkup());
        self::assertSame($reloaded->getCost(), $reloaded->getSellPrice());
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
