<?php

namespace App\Tests\Catalog\UI\Http;

use App\Shared\Domain\ValueObject\PriceModel;
use tests\Shared\Factory\CategoryFactory;
use tests\Shared\Factory\SubcategoryFactory;
use tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class SubcategoryControllerTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testIndexSubcategory(): void
    {
        SubcategoryFactory::createMany(3);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/subcategory/')
            ->assertSuccessful()
            ->assertSee('Subcategory Search')
            ->assertSee('3 results');
    }

    public function testSubcategorySecurity(): void
    {
        $this->browser()
            ->get('/subcategory/')
            ->assertOn('/login');
    }

    public function testFilterSubcategory(): void
    {
        SubcategoryFactory::createMany(3);
        SubcategoryFactory::createOne(['priceModel' => PriceModel::PRETTY_99]);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/subcategory/')
            ->assertSuccessful()
            ->assertSee('Subcategory Search')
            ->assertSee('4 results')
            ->get('/subcategory/search/filter')
            ->assertSuccessful()
            ->fillField('subcategory_search_filter[priceModel]', PriceModel::PRETTY_99->value)
            ->click('Update Filter')
            ->assertOn('/subcategory/?priceModel=pretty_99&filter=on')
            ->assertSee('Subcategory Search')
            ->assertSee('1 result');
    }

    public function testShowSubcategory(): void
    {
        $subcategory = SubcategoryFactory::createOne(['name' => 'Test Subcategory']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get("/subcategory/" . $subcategory->getId())
            ->assertSuccessful()
            ->assertSee('Test Subcategory');
    }

    public function testNewSubcategory(): void
    {
        $category = CategoryFactory::createOne(['name' => 'Test Category']);
        $owner = UserFactory::new()->asStaff()->create();
        $priceModel = PriceModel::DEFAULT;

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/subcategory/new')
            ->assertSuccessful()
            ->fillField('subcategory[name]','Test Subcategory')
            ->fillField('subcategory[category]', $category->getId())
            ->fillField('subcategory[defaultMarkup]','0.21')
            ->fillField('subcategory[priceModel]', $priceModel->value)
            ->fillField('subcategory[owner]', $owner->getId())
            ->fillField('subcategory[isActive]','1')
            ->click('Create Subcategory')
            ->assertOn('/subcategory/')
            ->assertSee('Test Subcategory');
    }

    public function testNewSubcategoryValidation(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/subcategory/new')
            ->assertSuccessful()
            // Intentionally filling form with invalid data
            ->fillField('subcategory[defaultMarkup]','-1')
            ->click('Create Subcategory')
            ->assertOn('/subcategory/new')
            ->assertSee('Please enter a Subcategory name')
            ->assertSee('Please enter a category')
            ->assertSee('Please enter a positive or zero subcategory markup %');
    }

    public function testEditSubcategory(): void
    {
        $subcategory = SubcategoryFactory::createOne(['name' => 'Subcategory to be edited']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get("/subcategory/" . $subcategory->getId() . "/edit")
            ->assertSuccessful()
            ->fillField('subcategory[name]','Edited Subcategory')
            ->click('Update Subcategory')
            ->assertOn('/subcategory/')
            ->assertSee('Edited Subcategory');
    }

    public function testEditSubCategoryValidation(): void
    {
        $subcategory = SubcategoryFactory::createOne(['name' => 'Subcategory to be edited']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get("/subcategory/" . $subcategory->getId() . "/edit")
            ->assertSuccessful()
            // Intentionally filling form with invalid data
            ->fillField('subcategory[name]','')
            ->fillField('subcategory[category]', '')
            ->fillField('subcategory[defaultMarkup]','-1')
            ->fillField('subcategory[priceModel]', '')
            ->click('Update Subcategory')
            ->assertOn("/subcategory/" . $subcategory->getId() . "/edit")
            ->assertSee('Please enter a Subcategory name')
            ->assertSee('Please enter a category')
            ->assertSee('Please enter a positive or zero subcategory markup %')
            ->assertSee('Please enter a price model');
    }

    public function testDeleteSubcategoryConfirmation(): void
    {
        $subcategory = SubcategoryFactory::createone(['name' => 'Subcategory to be deleted']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get("/subcategory/" . $subcategory->getId() . "/delete/confirm")
            ->assertSuccessful()
            ->assertSee('Are you sure you want to delete this Subcategory');
    }

    public function testDeleteSubcategory(): void
    {
        $subcategory = SubcategoryFactory::createone(['name' => 'Subcategory to be deleted']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get("/subcategory/" . $subcategory->getId() . "/delete/confirm")
            ->assertSuccessful()
            ->click('Delete')
            ->assertOn('/subcategory/')
            ->assertNotSee('Subcategory to be deleted');
    }

    public function testSubcategoryNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get("/subcategory/999")
            ->assertStatus(404);
    }
}
