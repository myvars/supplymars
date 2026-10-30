<?php

declare(strict_types=1);

namespace App\Tests\Pricing\UI;

use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SubcategoryFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\UI\PostedRecordId;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class UpdateSubcategoryCostFlowTest extends WebTestCase
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

    #[DataProvider('postedRecordIds')]
    public function testEditRefusesASubcategoryIdPostedInTheForm(string $postedId): void
    {
        $other = SubcategoryFactory::createOne(['defaultMarkup' => '5.000']);
        $subcategory = SubcategoryFactory::createOne(['defaultMarkup' => '5.000']);
        $product = ProductFactory::createOne(['subcategory' => $subcategory, 'category' => $subcategory->getCategory()]);

        $this->assertRefusesPostedRecordId(
            $this->browser()->actingAs(UserFactory::new()->asStaff()->create()),
            '/pricing/' . $product->getPublicId()->value() . '/cost/subcategory/edit',
            'Update Subcategory Cost',
            'id',
            $postedId,
            $other->getPublicId()->value(),
            ['defaultMarkup' => '7.500'],
        );

        SubcategoryFactory::assert()->exists(['id' => $other->getId(), 'defaultMarkup' => '5.000']);
        SubcategoryFactory::assert()->exists(['id' => $subcategory->getId(), 'defaultMarkup' => '5.000']);
    }
}
