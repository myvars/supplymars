<?php

namespace App\Tests\Catalog\UI;

use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\ManufacturerFactory;
use App\Tests\Shared\Factory\SubcategoryFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class CreateProductFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testSuccessfulCreationViaForm(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        $owner = UserFactory::new()->asStaff()->create();
        $category = CategoryFactory::createOne();
        $subcategory = SubcategoryFactory::createOne(['category' => $category]);
        $manufacturer = ManufacturerFactory::createOne();

        $this->browser()
            ->actingAs($user)
            ->get('/product/new')
            ->fillField('product[name]', 'Flow Product')
            ->fillField('product[description]', 'Flow product description')
            ->fillField('product[category]', (string) $category->getId())
            ->click('product_auto-update')
            ->fillField('product[subcategory]', (string) $subcategory->getId())
            ->fillField('product[manufacturer]', (string) $manufacturer->getId())
            ->fillField('product[mfrPartNumber]', 'FLOW-1234')
            ->fillField('product[owner]', (string) $owner->getId())
            ->fillField('product[isActive]', '1')
            ->click('Create Product')
            ->assertOn('/product/')
            ->assertSee('Flow Product');
    }

    public function testValidationErrorsOnEmptySubmission(): void
    {
        $user = UserFactory::new()->asStaff()->create();

        $this->browser()
            ->actingAs($user)
            ->get('/product/new')
            ->click('Create Product')
            ->assertOn('/product/new')
            ->assertSee('Please enter a product name')
            ->assertSee('Please choose a Category')
            ->assertSee('Please choose a Subcategory')
            ->assertSee('Please choose a Manufacturer')
            ->assertSee('Please enter a manufacturer part number');
    }
}
