<?php

declare(strict_types=1);

namespace App\Tests\Pricing\UI;

use App\Catalog\Domain\Model\Product\Product;
use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\UI\PostedRecordId;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class UpdateProductCostFlowTest extends WebTestCase
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
            ->get('/pricing/' . $publicId . '/cost/product/edit')
            ->fillField('product_cost[defaultMarkup]', '7.500')
            ->fillField('product_cost[priceModel]', PriceModel::PRETTY_99->value)
            ->click('Update Product Cost')
            ->assertOn('/pricing/' . $publicId . '/cost')
            ->assertSee('Product cost updated');

        self::assertSame('7.500', $product->getDefaultMarkup());
        self::assertSame(PriceModel::PRETTY_99, $product->getPriceModel());
    }

    public function testValidationErrorsOnInvalidSubmission(): void
    {
        $admin = UserFactory::new()->asStaff()->create();
        $product = ProductFactory::new()->withActiveSource()->create();
        $publicId = $product->getPublicId()->value();

        $this->browser()
            ->actingAs($admin)
            ->get('/pricing/' . $publicId . '/cost/product/edit')
            ->fillField('product_cost[defaultMarkup]', '-1.000')
            ->click('Update')
            ->assertOn('/pricing/' . $publicId . '/cost/product/edit')
            ->assertSee('Please enter a positive or zero product markup %');
    }

    #[DataProvider('postedRecordIds')]
    public function testEditRefusesAProductIdPostedInTheForm(string $postedId): void
    {
        $other = ProductFactory::createOne();
        $product = ProductFactory::createOne();

        $this->assertRefusesPostedRecordId(
            $this->browser()->actingAs(UserFactory::new()->asStaff()->create()),
            '/pricing/' . $product->getPublicId()->value() . '/cost/product/edit',
            'Update Product Cost',
            'id',
            $postedId,
            $other->getPublicId()->value(),
            ['defaultMarkup' => '7.500'],
        );

        ProductFactory::assert()->exists(['id' => $other->getId(), 'defaultMarkup' => Product::DEFAULT_MARKUP]);
        ProductFactory::assert()->exists(['id' => $product->getId(), 'defaultMarkup' => Product::DEFAULT_MARKUP]);
    }
}
