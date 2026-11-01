<?php

declare(strict_types=1);

namespace App\Tests\Pricing\UI;

use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\UI\PostedRecordId;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class UpdateCategoryCostFlowTest extends WebTestCase
{
    use HasBrowser;
    use PostedRecordId;

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

        self::assertSame('0.00', $product->getCost());
        self::assertSame('0.00', $product->getSellPriceIncVat());
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

        self::assertSame('0.000', $product->getMarkup());
        self::assertSame($product->getCost(), $product->getSellPrice());
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

    #[DataProvider('postedRecordIds')]
    public function testEditRefusesACategoryIdPostedInTheForm(string $postedId): void
    {
        $other = CategoryFactory::createOne(['defaultMarkup' => '5.000']);
        $category = CategoryFactory::createOne(['defaultMarkup' => '5.000']);
        $product = ProductFactory::createOne(['category' => $category]);

        $this->assertRefusesPostedRecordId(
            $this->browser()->actingAs(UserFactory::new()->asStaff()->create()),
            '/pricing/' . $product->getPublicId()->value() . '/cost/category/edit',
            'Update Category Cost',
            'id',
            $postedId,
            $other->getPublicId()->value(),
            ['defaultMarkup' => '7.500'],
        );

        CategoryFactory::assert()->exists(['id' => $other->getId(), 'defaultMarkup' => '5.000']);
        CategoryFactory::assert()->exists(['id' => $category->getId(), 'defaultMarkup' => '5.000']);
    }
}
