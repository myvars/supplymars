<?php

namespace App\Tests\Catalog\UI\Http;

use App\Shared\Domain\ValueObject\PriceModel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use tests\Shared\Factory\CategoryFactory;
use tests\Shared\Factory\UserFactory;
use tests\Shared\Factory\VatRateFactory;
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
            ->actingAs(UserFactory::new()->asStaff()->create())
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
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/category/')
            ->assertSuccessful()
            ->assertSee('Category Search')
            ->assertSee('4 results')
            ->get('/category/search/filter')
            ->assertSuccessful()
            ->fillField('category_filter[priceModel]', PriceModel::PRETTY_99->value)
            ->click('Apply Filter')
            ->assertOn('/category/?sort=id&sortDirection=ASC&page=1&limit=5&priceModel=pretty_99&filter=on')
            ->assertSee('Category Search')
            ->assertSee('1 result');
    }

    public function testShowCategory(): void
    {
        $category = CategoryFactory::createone(['name' => 'Test Category']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/category/' . $category->getPublicId())
            ->assertSuccessful()
            ->assertSee('Test Category');
    }

    public function testNewCategory(): void
    {
        $vatRate = VatRateFactory::createOne(['name' => 'Test VatRate']);
        $owner = UserFactory::new()->asStaff()->create();
        $priceModel = PriceModel::DEFAULT;

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/category/new')
            ->assertSuccessful()
            ->fillField('category[name]', 'Test Category')
            ->fillField('category[vatRate]', $vatRate->getId())
            ->fillField('category[defaultMarkup]', '0.21')
            ->fillField('category[priceModel]', $priceModel->value)
            ->fillField('category[owner]', $owner->getId())
            ->fillField('category[isActive]', '1')
            ->click('Create Category')
            ->assertOn('/category/')
            ->assertSee('Test Category');
    }

    public function testNewCategoryValidation(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/category/new')
            ->assertSuccessful()
            // Intentionally omitting form data or filling it with invalid data
            ->click('Create Category')
            ->assertOn('/category/new')
            ->assertSee('Please enter a category name')
            ->assertSee('Please choose a VAT rate')
            ->assertSee('Please choose a category owner');
    }

    public function testEditCategory(): void
    {
        $owner = UserFactory::new()->asStaff()->create();
        $category = CategoryFactory::createone(['name' => 'Category to be edited', 'owner' => $owner]);
        $user = UserFactory::new()->asStaff()->create();

        $this->browser()
            ->actingAs($user)
            ->get('/category/' . $category->getPublicId() . '/edit')
            ->assertSuccessful()
            ->fillField('category[name]', 'Edited Category')
            ->click('Update Category')
            ->assertOn('/category/')
            ->assertSee('Edited Category');
    }

    public function testEditCategoryValidation(): void
    {
        $category = CategoryFactory::createone(['name' => 'Category to be edited']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/category/' . $category->getPublicId() . '/edit')
            ->assertSuccessful()
            // Intentionally filling form with invalid data
            ->fillField('category[name]', '')
            ->fillField('category[vatRate]', '')
            ->fillField('category[defaultMarkup]', '-1')
            ->fillField('category[priceModel]', '')
            ->fillField('category[owner]', '')
            ->click('Update Category')
            ->assertOn('/category/' . $category->getPublicId() . '/edit')
            ->assertSee('Please enter a category name')
            ->assertSee('Please choose a VAT rate')
            ->assertSee('Please enter a positive or zero category markup %')
            ->assertSee('Please choose a price model')
            ->assertSee('Please choose a category owner');
    }

    public function testDeleteCategoryConfirmation(): void
    {
        $category = CategoryFactory::createone(['name' => 'Category to be deleted']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/category/' . $category->getPublicId() . '/delete/confirm')
            ->assertSuccessful()
            ->assertSee('Are you sure you want to delete this Category');
    }

    public function testDeleteCategory(): void
    {
        $category = CategoryFactory::createone(['name' => 'Category to be deleted']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/category/' . $category->getPublicId() . '/delete/confirm')
            ->assertSuccessful()
            ->click('Delete')
            ->assertOn('/category/')
            ->assertNotSee('Category to be deleted');
    }

    public function testCategoryNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/category/999')
            ->assertStatus(500);
    }
}
