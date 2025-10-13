<?php

namespace App\Tests\Pricing\UI\Http;

use tests\Shared\Factory\ProductFactory;
use tests\Shared\Factory\SupplierProductFactory;
use tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class ProductStockControllerTest extends WebTestCase
{
    use HasBrowser;
    use Factories;


    public function testProductStockSecurity(): void
    {
        $product = ProductFactory::createOne(['name' => 'Product to be shown']);

        $this->browser()
            ->get("/product/" . $product->getId() . "/stock")
            ->assertOn('/login');
    }

    public function testShowProductStockWithoutSource(): void
    {
        $product = ProductFactory::createOne(['name' => 'Product to be shown']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get("/product/" . $product->getId() . "/stock")
            ->assertSuccessful()
            ->assertSee('Product Stock');
    }

    public function testShowProductStockWithInactiveProduct(): void
    {
        $product = ProductFactory::createOne([
            'name' => 'Product to be shown',
            'isActive' => false,
        ]);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get("/product/" . $product->getId() . "/stock")
            ->assertSuccessful()
            ->assertSee('Product Stock')
            ->assertSee('Incomplete Product');

    }

    public function testProductStockWithSource(): void
    {
        $supplierProduct = SupplierProductFactory::createOne();
        $product = $supplierProduct->getProduct();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get("/product/" . $product->getId() . "/stock")
            ->assertSuccessful()
            ->assertSee('Product Stock')
            ->assertNotSee('Incomplete Product');
    }

    public function testShowProductStockNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get("/product/999/stock")
            ->assertStatus(404);
    }
}
