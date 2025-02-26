<?php

namespace App\Tests\Application\Controller;

use App\Factory\ProductFactory;
use App\Factory\SupplierProductFactory;
use App\Factory\UserFactory;
use App\Service\Product\ProductPriceCalculator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class ProductCostControllerTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    private ProductPriceCalculator $productPriceCalculator;

    protected function setUp(): void
    {
        $this->productPriceCalculator = self::getContainer()->get(ProductPriceCalculator::class);
    }

    public function testShowProductCost(): void
    {
        $product = ProductFactory::createOne(['name' => 'Product to be shown']);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/product/" . $product->getId() . "/cost")
            ->assertSuccessful()
            ->assertSee('Product Cost');
    }

    public function testProductCostSecurity(): void
    {
        $product = ProductFactory::createOne(['name' => 'Product to be shown']);

        $this->browser()
            ->get("/product/" . $product->getId() . "/cost")
            ->assertOn('/login');
    }

    public function testShowProductCostWithActiveProductCategorySubcategoryAndSource(): void
    {
        $supplierProduct = SupplierProductFactory::createOne();
        $product = $supplierProduct->getProduct();

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/product/" . $product->getId() . "/cost")
            ->assertSuccessful()
            ->assertSee('Product Cost')
            ->assertNotSee('Inactive Product');
    }

    public function testShowProductCostWithInactiveProduct(): void
    {
        $supplierProduct = SupplierProductFactory::createOne();
        $product = $supplierProduct->getProduct();

        $product->setIsActive(false);

        $this->productPriceCalculator->recalculatePrice($product);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/product/" . $product->getId() . "/cost")
            ->assertSuccessful()
            ->assertSee('Product Cost')
            ->assertSee('Incomplete Product');
    }

    public function testShowProductCostWithInactiveCategory(): void
    {
        $supplierProduct = SupplierProductFactory::createOne();
        $product = $supplierProduct->getProduct();

        $product->getCategory()->setIsActive(false);
        $this->productPriceCalculator->recalculatePrice($product);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/product/" . $product->getId() . "/cost")
            ->assertSuccessful()
            ->assertSee('Product Cost')
            ->assertSee('Incomplete Product');
    }

    public function testShowProductCostWithInactiveSubcategory(): void
    {
        $supplierProduct = SupplierProductFactory::createOne();
        $product = $supplierProduct->getProduct();

        $product->getSubcategory()->setIsActive(false);
        $this->productPriceCalculator->recalculatePrice($product);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/product/" . $product->getId() . "/cost")
            ->assertSuccessful()
            ->assertSee('Product Cost')
            ->assertSee('Incomplete Product');
    }

    public function testShowProductCostNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/product/999/cost")
            ->assertStatus(404);
    }

    public function testEditProductCost(): void
    {
        $product = ProductFactory::createOne(['name' => 'Test Product']);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/product/" . $product->getId() . "/cost/edit")
            ->assertSuccessful()
            ->fillField('product_cost[defaultMarkup]','12.345')
            ->click('Update Product Cost')
            ->assertOn("/product/" . $product->getId() . "/cost")
            ->assertSee('12.345');
    }

    public function testEditProductCostValidation(): void
    {
        $product = ProductFactory::createOne([ 'name' => 'Test Product']);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/product/" . $product->getId() . "/cost/edit")
            ->assertSuccessful()
            ->fillField('product_cost[defaultMarkup]','-1')
            ->fillField('product_cost[priceModel]','')
            ->click('Update Product Cost')
            ->assertOn("/product/" . $product->getId() . "/cost/edit")
            ->assertSee('Please enter a positive or zero product markup %')
            ->assertSee('Please enter a price model');
    }

    public function testEditProductCostNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/product/999/cost/edit")
            ->assertStatus(404);
    }

    public function testEditCategoryCost(): void
    {
        $product = ProductFactory::createOne(['name' => 'Test Product']);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/product/" . $product->getId() . "/cost/category/edit")
            ->assertSuccessful()
            ->fillField('category_cost[defaultMarkup]','12.345')
            ->click('Update Category Cost')
            ->assertOn("/product/" . $product->getId() . "/cost")
            ->assertSee('12.345');
    }

    public function testEditCategoryCostValidation(): void
    {
        $product = ProductFactory::createOne([ 'name' => 'Test Product']);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/product/" . $product->getId() . "/cost/category/edit")
            ->assertSuccessful()
            ->fillField('category_cost[defaultMarkup]','-1')
            ->fillField('category_cost[priceModel]','')
            ->click('Update Category Cost')
            ->assertOn("/product/" . $product->getId() . "/cost/category/edit")
            ->assertSee('Please enter a positive or zero category markup %')
            ->assertSee('Please enter a price model');
    }

    public function testEditCategoryCostNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/product/999/cost/category/edit")
            ->assertStatus(404);
    }

    public function testEditSubcategoryCost(): void
    {
        $product = ProductFactory::createOne(['name' => 'Test Product']);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/product/" . $product->getId() . "/cost/subcategory/edit")
            ->assertSuccessful()
            ->fillField('subcategory_cost[defaultMarkup]','12.345')
            ->click('Update Subcategory Cost')
            ->assertOn("/product/" . $product->getId() . "/cost")
            ->assertSee('12.345');
    }

    public function testEditSubcategoryCostValidation(): void
    {
        $product = ProductFactory::createOne([ 'name' => 'Test Product']);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/product/" . $product->getId() . "/cost/subcategory/edit")
            ->assertSuccessful()
            ->fillField('subcategory_cost[defaultMarkup]','-1')
            ->fillField('subcategory_cost[priceModel]','')
            ->click('Update Subcategory Cost')
            ->assertOn("/product/" . $product->getId() . "/cost/subcategory/edit")
            ->assertSee('Please enter a positive or zero subcategory markup %')
            ->assertSee('Please enter a price model');
    }

    public function testEditSubcategoryCostNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/product/999/cost/subcategory/edit")
            ->assertStatus(404);
    }
}