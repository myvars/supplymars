<?php

namespace App\Tests\Application\Controller;

use App\Enum\PriceModel;
use App\Factory\CategoryFactory;
use App\Factory\UserFactory;
use App\Factory\VatRateFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class CategoryControllerTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testIndexCategory(): void
    {
        CategoryFactory::createMany(3);

        $this->browser()
            ->get('/category/')
            ->assertSuccessful()
            ->assertSee('Category List')
            ->assertSee('3 results');
    }

    public function testShowCategory(): void
    {
        $category = CategoryFactory::createone(['name' => 'Category to be shown']);

        $this->browser()
            ->get("/category/" . $category->getId())
            ->assertSuccessful()
            ->assertSee('Category to be shown');
    }

    public function testNewCategory(): void
    {
        $vatRate = VatRateFactory::createOne(['name' => 'Test VatRate']);
        $owner = UserFactory::createOne(['fullName' => 'Test Owner']);
        $priceModel = PriceModel::DEFAULT;

        $this->browser()
            ->get('/category/new')
            ->assertSuccessful()
            ->fillField('category[name]','Test Category')
            ->fillField('category[vatRate]', $vatRate->getId())
            ->fillField('category[defaultMarkup]','0.21')
            ->fillField('category[priceModel]', $priceModel->value)
            ->fillField('category[owner]', $owner->getId())
            ->fillField('category[isActive]','1')
            ->click('Create Category')
            ->assertOn('/category/')
            ->assertSee('Test Category');
    }

    public function testNewCategoryValidation(): void
    {
        $this->browser()
            ->get('/category/new')
            ->assertSuccessful()
            // Intentionally omitting form data or filling it with invalid data
            ->click('Create Category')
            ->assertOn('/category/new')
            ->assertSee('Please enter a category name')
            ->assertSee('Please enter a VAT rate')
            ->assertSee('Please enter a category owner');
    }

    public function testEditCategory(): void
    {
        $category = CategoryFactory::createone(['name' => 'Category to be edited']);

        $this->browser()
            ->get("/category/" . $category->getId() . "/edit")
            ->assertSuccessful()
            ->fillField('category[name]','Edited Category')
            ->click('Update Category')
            ->assertOn('/category/')
            ->assertSee('Edited Category');
    }

    public function testEditCategoryValidation(): void
    {
        $category = CategoryFactory::createone(['name' => 'Category to be edited']);

        $this->browser()
            ->get("/category/" . $category->getId() . "/edit")
            ->assertSuccessful()
            // Intentionally filling form with invalid data
            ->fillField('category[name]','')
            ->fillField('category[vatRate]', '')
            ->fillField('category[defaultMarkup]','-1')
            ->fillField('category[priceModel]', '')
            ->fillField('category[owner]', '')
            ->click('Update Category')
            ->assertOn("/category/" . $category->getId() . "/edit")
            ->assertSee('Please enter a category name')
            ->assertSee('Please enter a VAT rate')
            ->assertSee('Please enter a positive or zero category markup %')
            ->assertSee('Please enter a price model')
            ->assertSee('Please enter a category owner');
    }

    public function testDeleteCategoryConfirmation(): void
    {
        $category = CategoryFactory::createone(['name' => 'Category to be deleted']);

        $this->browser()
            ->get("/category/" . $category->getId() . "/delete/confirm")
            ->assertSuccessful()
            ->assertSee('Are you sure you want to delete this Category');
    }

    public function testDeleteCategory(): void
    {
        $category = CategoryFactory::createone(['name' => 'Category to be deleted']);

        $this->browser()
            ->get("/category/" . $category->getId() . "/delete/confirm")
            ->assertSuccessful()
            ->click('Delete')
            ->assertOn('/category/')
            ->assertNotSee('Category to be deleted');
    }

    public function testCategoryNotFound(): void
    {
        $this->browser()
            ->get("/category/999")
            ->assertSee("Sorry, we can't find that Category");
    }
}