<?php

namespace App\Tests\Application\Controller;

use App\Factory\ProductFactory;
use App\Factory\UserFactory;
use App\Service\Product\ActiveSourceCalculator;
use App\Service\Product\ProductPriceCalculator;
use App\Tests\Utilities\TestProduct;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class ProductCostControllerTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    private ProductPriceCalculator $productPriceCalculator;

    private TestProduct $testProduct;

    protected function setUp(): void
    {
        $this->productPriceCalculator = self::getContainer()->get(ProductPriceCalculator::class);
        $this->testProduct = new TestProduct(
            self::getContainer()->get(EntityManagerInterface::class),
            self::getContainer()->get(ActiveSourceCalculator::class),
            $this->productPriceCalculator
        );
    }

    public function testShowProductCost(): void
    {
        $product = ProductFactory::createOne(['name' => 'Product to be shown']);

        $this->browser()
            ->actingAs(UserFactory::staff())
            ->get("/product/" . $product->getId() . "/cost")
            ->assertSuccessful()
            ->assertSee('Product Cost');
    }

    public function testShowProductCostWithActiveProductCategorySubcategoryAndSource(): void
    {
        $product = $this->testProduct->create();

        $this->browser()
            ->actingAs(UserFactory::staff())
            ->get("/product/" . $product->getId() . "/cost")
            ->assertSuccessful()
            ->assertSee('Product Cost')
            ->assertNotSee('Inactive Product');
    }

    public function testShowProductCostWithInactiveProduct(): void
    {
        $product = $this->testProduct->create();

        $product->setIsActive(false);

        $this->productPriceCalculator->recalculatePrice($product);

        $this->browser()
            ->actingAs(UserFactory::staff())
            ->get("/product/" . $product->getId() . "/cost")
            ->assertSuccessful()
            ->assertSee('Product Cost')
            ->assertSee('Incomplete Product');
    }

    public function testShowProductCostWithInactiveCategory(): void
    {
        $product = $this->testProduct->create();

        $product->getCategory()->setIsActive(false);
        $this->productPriceCalculator->recalculatePrice($product);

        $this->browser()
            ->actingAs(UserFactory::staff())
            ->get("/product/" . $product->getId() . "/cost")
            ->assertSuccessful()
            ->assertSee('Product Cost')
            ->assertSee('Incomplete Product');
    }

    public function testShowProductCostWithInactiveSubcategory(): void
    {
        $product = $this->testProduct->create();

        $product->getSubcategory()->setIsActive(false);
        $this->productPriceCalculator->recalculatePrice($product);

        $this->browser()
            ->actingAs(UserFactory::staff())
            ->get("/product/" . $product->getId() . "/cost")
            ->assertSuccessful()
            ->assertSee('Product Cost')
            ->assertSee('Incomplete Product');
    }

    public function testShowProductCostWithInvalidProduct(): void
    {
        $this->browser()
            ->actingAs(UserFactory::staff())
            ->get("/product/999/cost")
            ->assertSee("Sorry, we can't find that Product");
    }

    public function testEditProductCost(): void
    {
        $product = ProductFactory::createOne([
            'name' => 'Test Product',
            'defaultMarkup' => '0.000',
        ]);

        $this->browser()
            ->actingAs(UserFactory::staff())
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
            ->actingAs(UserFactory::staff())
            ->get("/product/" . $product->getId() . "/cost/edit")
            ->assertSuccessful()
            ->fillField('product_cost[defaultMarkup]','-1')
            ->fillField('product_cost[priceModel]','')
            ->click('Update Product Cost')
            ->assertOn("/product/" . $product->getId() . "/cost/edit")
            ->assertSee('Please enter a positive or zero product markup %')
            ->assertSee('Please enter a price model');
    }

    public function testEditProductCostWithInvalidProduct(): void
    {
        $this->browser()
            ->actingAs(UserFactory::staff())
            ->get("/product/999/cost/edit")
            ->assertSee("Sorry, we can't find that Product");
    }

    public function testEditCategoryCost(): void
    {
        $product = ProductFactory::createOne([
            'name' => 'Test Product',
            'defaultMarkup' => '0.000',
        ]);

        $this->browser()
            ->actingAs(UserFactory::staff())
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
            ->actingAs(UserFactory::staff())
            ->get("/product/" . $product->getId() . "/cost/category/edit")
            ->assertSuccessful()
            ->fillField('category_cost[defaultMarkup]','-1')
            ->fillField('category_cost[priceModel]','')
            ->click('Update Category Cost')
            ->assertOn("/product/" . $product->getId() . "/cost/category/edit")
            ->assertSee('Please enter a positive or zero category markup %')
            ->assertSee('Please enter a price model');
    }

    public function testEditCategoryCostWithInvalidProduct(): void
    {
        $this->browser()
            ->actingAs(UserFactory::staff())
            ->get("/product/999/cost/category/edit")
            ->assertSee("Sorry, we can't find that Product");
    }

    public function testEditSubcategoryCost(): void
    {
        $product = ProductFactory::createOne([
            'name' => 'Test Product',
            'defaultMarkup' => '0.000',
        ]);

        $this->browser()
            ->actingAs(UserFactory::staff())
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
            ->actingAs(UserFactory::staff())
            ->get("/product/" . $product->getId() . "/cost/subcategory/edit")
            ->assertSuccessful()
            ->fillField('subcategory_cost[defaultMarkup]','-1')
            ->fillField('subcategory_cost[priceModel]','')
            ->click('Update Subcategory Cost')
            ->assertOn("/product/" . $product->getId() . "/cost/subcategory/edit")
            ->assertSee('Please enter a positive or zero subcategory markup %')
            ->assertSee('Please enter a price model');
    }

    public function testEditSubcategoryCostWithInvalidProduct(): void
    {
        $this->browser()
            ->actingAs(UserFactory::staff())
            ->get("/product/999/cost/subcategory/edit")
            ->assertSee("Sorry, we can't find that Product");
    }
}