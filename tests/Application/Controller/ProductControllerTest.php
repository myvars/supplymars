<?php

namespace App\Tests\Application\Controller;

use App\Factory\ManufacturerFactory;
use App\Factory\ProductFactory;
use App\Factory\SubcategoryFactory;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class ProductControllerTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testIndexProduct(): void
    {
        ProductFactory::createMany(3);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/product/')
            ->assertSuccessful()
            ->assertSee('Product Search')
            ->assertSee('3 results');
    }

    public function testIndexProductPageNotInRange(): void
    {
        ProductFactory::createOne();

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/product/?page=2')
            ->assertSuccessful()
            ->assertSee('Product Search')
            ->assertSee('Page 2 not found');
    }

    public function testShowProduct(): void
    {
        $product = ProductFactory::createOne(['name' => 'Product to be shown']);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/product/" . $product->getId())
            ->assertSuccessful()
            ->assertSee('Product to be shown');
    }

    public function testNewProduct(): void
    {
        $subcategory = SubcategoryFactory::createOne(['name' => 'Test Subcategory']);
        $manufacturer = ManufacturerFactory::createOne(['name' => 'Test Manufacturer']);
$owner = UserFactory::new()->staff()->create();

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/product/new')
            ->assertSuccessful()
            ->fillField('product[name]','Test Product')
            ->fillField('product[category]', $subcategory->getCategory()->getId())
            ->click('Create Product')
            ->fillField('product[subcategory]', $subcategory->getId())
            ->fillField('product[manufacturer]', $manufacturer->getId())
            ->fillField('product[owner]', $owner->getId())
            ->fillField('product[cost]','500')
            ->fillField('product[mfrPartNumber]','12345')
            ->click('Create Product')
            ->assertOn('/product/')
            ->assertSee('Test Product');
    }

    public function testNewProductValidation(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get('/product/new')
            ->assertSuccessful()
            // Intentionally filling form with invalid data
            ->click('Create Product')
            ->assertOn('/product/new')
            ->assertSee('Please enter a product name')
            ->assertSee('Please enter a category')
            ->assertSee('Please enter a subcategory')
            ->assertSee('Please enter a manufacturer')
            ->assertSee('Please enter a cost')
            ->assertSee('Please enter a manufacturer part number');
    }

    public function testEditProduct(): void
    {
        $subcategory = SubcategoryFactory::createOne(['name' => 'Test Subcategory']);
        $product = ProductFactory::createOne([
            'name' => 'Product to be edited',
            'category' => $subcategory->getCategory(),
            'subcategory' => $subcategory,
        ]);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/product/" . $product->getId() . "/edit")
            ->assertSuccessful()
            ->fillField('product[name]','Edited Product')
            ->click('Update Product')
            ->assertOn('/product/')
            ->assertSee('Edited Product');
    }

    public function testEditProductValidation(): void
    {
        $product = ProductFactory::createOne(['name' => 'Product to be edited']);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/product/" . $product->getId() . "/edit")
            ->assertSuccessful()
            // Intentionally filling form with invalid data
            ->fillField('product[name]','')
            ->fillField('product[category]', '')
            ->fillField('product[subcategory]', '')
            ->fillField('product[manufacturer]', '')
            ->fillField('product[cost]','')
            ->fillField('product[mfrPartNumber]','')
            ->click('Update Product')
            ->assertOn("/product/" . $product->getId() . "/edit")
            ->assertSee('Please enter a product name')
            ->assertSee('Please enter a category')
            ->assertSee('Please enter a subcategory')
            ->assertSee('Please enter a manufacturer')
            ->assertSee('Please enter a cost')
            ->assertSee('Please enter a manufacturer part number');
    }

    public function testDeleteProductConfirmation(): void
    {
        $product = ProductFactory::createOne(['name' => 'Product to be deleted']);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/product/" . $product->getId() . "/delete/confirm")
            ->assertSuccessful()
            ->assertSee('Are you sure you want to delete this Product');
    }

    public function testDeleteProduct(): void
    {
        $product = ProductFactory::createOne(['name' => 'Product to be deleted']);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/product/" . $product->getId() . "/delete/confirm")
            ->assertSuccessful()
            ->click('Delete')
            ->assertOn('/product/')
            ->assertNotSee('Product to be deleted');
    }

    public function testProductNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/product/999")
            ->assertSee("Sorry, we can't find that Product");
    }
}