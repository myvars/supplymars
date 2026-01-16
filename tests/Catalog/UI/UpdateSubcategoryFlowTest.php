<?php

namespace App\Tests\Catalog\UI;

use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\SubcategoryFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class UpdateSubcategoryFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testSuccessfulUpdateViaForm(): void
    {
        $subcategory = SubcategoryFactory::new()->create();
        $publicId = $subcategory->getPublicId()->value();
        $newCategory = CategoryFactory::new()->create();
        $newOwner = UserFactory::new()->asStaff()->create();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/subcategory/' . $publicId . '/edit')
            ->fillField('subcategory[category]', $newCategory->getId())
            ->fillField('subcategory[name]', 'Flow Updated Subcategory')
            ->fillField('subcategory[defaultMarkup]', '7.500')
            ->fillField('subcategory[priceModel]', PriceModel::PRETTY_99->value)
            ->fillField('subcategory[owner]', $newOwner->getId())
            ->uncheckField('subcategory[isActive]')
            ->click('Update Subcategory')
            ->assertOn('/subcategory/')
            ->assertSee('Flow Updated Subcategory');
    }

    public function testValidationErrorsOnEmptyName(): void
    {
        $subcategory = SubcategoryFactory::new()->create();
        $publicId = $subcategory->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/subcategory/' . $publicId . '/edit')
            ->fillField('subcategory[name]', '')
            ->click('Update Subcategory')
            ->assertOn('/subcategory/' . $publicId . '/edit')
            ->assertSee('Please enter a subcategory name');
    }
}
