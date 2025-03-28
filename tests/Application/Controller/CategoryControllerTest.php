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
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/category/')
            ->assertSuccessful()
            ->assertSee('Category Search')
            ->assertSee('3 results');
    }

    public function testCategorySecurity(): void
    {
        $this->browser()
            ->get('/category/')
            ->assertOn('/login');
    }

    public function testFilterCategory(): void
    {
        CategoryFactory::createMany(3);
        CategoryFactory::createOne(['priceModel' => PriceModel::PRETTY_99]);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/category/')
            ->assertSuccessful()
            ->assertSee('Category Search')
            ->assertSee('4 results')
            ->get('/category/search/filter')
            ->assertSuccessful()
            ->fillField('category_search_filter[priceModel]', PriceModel::PRETTY_99->value)
            ->click('Update Filter')
            ->assertOn('/category/?priceModel=pretty_99&filter=on')
            ->assertSee('Category Search')
            ->assertSee('1 result');
    }

    public function testShowCategory(): void
    {
        $category = CategoryFactory::createone(['name' => 'Test Category']);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/category/" . $category->getId())
            ->assertSuccessful()
            ->assertSee('Test Category');
    }

    public function testNewCategory(): void
    {
        $vatRate = VatRateFactory::createOne(['name' => 'Test VatRate']);
        $owner = UserFactory::new()->staff()->create();
        $priceModel = PriceModel::DEFAULT;

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
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
            ->actingAs(UserFactory::new()->staff()->create())
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
        $owner = UserFactory::new()->staff()->create();
        $category = CategoryFactory::createone(['name' => 'Category to be edited', 'owner' => $owner]);
        $user = UserFactory::new()->staff()->create();

        $this->browser()
            ->actingAs($user)
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
            ->actingAs(UserFactory::new()->staff()->create())
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
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/category/" . $category->getId() . "/delete/confirm")
            ->assertSuccessful()
            ->assertSee('Are you sure you want to delete this Category');
    }

    public function testDeleteCategory(): void
    {
        $category = CategoryFactory::createone(['name' => 'Category to be deleted']);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/category/" . $category->getId() . "/delete/confirm")
            ->assertSuccessful()
            ->click('Delete')
            ->assertOn('/category/')
            ->assertNotSee('Category to be deleted');
    }

    public function testCategoryNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/category/999")
            ->assertSee("Category not found!");
    }
}