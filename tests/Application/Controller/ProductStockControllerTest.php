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

class ProductStockControllerTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    private ActiveSourceCalculator $activeSourceCalculator;

    private TestProduct $testProduct;

    protected function setUp(): void
    {
        $this->activeSourceCalculator = self::getContainer()->get(ActiveSourceCalculator::class);
        $this->testProduct = new TestProduct(
            static::getContainer()->get(EntityManagerInterface::class),
            $this->activeSourceCalculator,
            self::getContainer()->get(ProductPriceCalculator::class)
        );
    }

    public function testShowProductStock(): void
    {
        $product = ProductFactory::createOne(['name' => 'Product to be shown']);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
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
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/product/" . $product->getId() . "/stock")
            ->assertSuccessful()
            ->assertSee('Product Stock')
            ->assertSee('Incomplete Product');

    }

    public function testShowProductStockWithInvalidProduct(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/product/999/stock")
            ->assertSee("Sorry, we can't find that Product");
    }

    public function testProductStockSourceExists(): void
    {
        $product = $this->testProduct->create();

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/product/" . $product->getId() . "/stock")
            ->assertSuccessful()
            ->assertSee('Product Stock');
    }

    public function testProductSourceRemoveConfirm(): void
    {
        $product = $this->testProduct->create();

        $supplierProduct = $product->getActiveProductSource();

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/supplier-product/" . $supplierProduct->getId() . "/remove")
            ->assertSee('Are you sure you want to remove this supplier product?');
    }

    public function testProductSourceRemove(): void
    {
        $product = $this->testProduct->create();

        $supplierProduct = $product->getActiveProductSource();

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/supplier-product/" . $supplierProduct->getId() . "/remove")
            ->click('Remove')
            ->get("/product/" . $product->getId() . "/stock")
            ->assertSuccessful()
            ->assertSee('Product Stock');

    }

    public function testProductSourceRemoveWithNoProductSource(): void
    {
        $product = $this->testProduct->create();

        $supplierProduct = $product->getActiveProductSource();
        $product->removeSupplierProduct($supplierProduct);
        $product->setActiveProductSource(null);

        $this->activeSourceCalculator->recalculateActiveSource($product);

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/supplier-product/" . $supplierProduct->getId() . "/remove")
            ->assertSee("Sorry, we can't find that Supplier Product");
    }

    public function testProductSourceRemoveNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/supplier-product/999/remove")
            ->assertSee("Sorry, we can't find that Supplier Product");
    }

    public function testProductSourceStatusToggle(): void
    {
        $product = $this->testProduct->create();

        $supplierProduct = $product->getActiveProductSource();

        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/product/" . $product->getId() . "/stock")
            ->assertSuccessful()
            ->assertSee('Product Stock')
            ->assertNotSee('Incomplete Product')
            ->get("/supplier-product/" . $supplierProduct->getId() . "/status/toggle")
            ->get("/product/" . $product->getId() . "/stock")
            ->assertSuccessful()
            ->assertSee('Incomplete Product')
            ->get("/supplier-product/" . $supplierProduct->getId() . "/status/toggle")
            ->get("/product/" . $product->getId() . "/stock")
            ->assertSuccessful()
            ->assertNotSee('Incomplete Product');
    }

    public function testProductSourceStatusToggleNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->staff()->create())
            ->get("/supplier-product/999/status/toggle")
            ->assertSee("Sorry, we can't find that Supplier Product");
    }
}