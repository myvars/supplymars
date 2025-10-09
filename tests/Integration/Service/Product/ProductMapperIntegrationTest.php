<?php

namespace App\Tests\Integration\Service\Product;

use App\Entity\Product;
use App\Factory\ManufacturerFactory;
use App\Factory\ProductFactory;
use App\Factory\SubcategoryFactory;
use App\Factory\SupplierProductFactory;
use App\Service\Product\ProductMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class ProductMapperIntegrationTest extends KernelTestCase
{
    use Factories;

    private ProductMapper $productMapper;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $this->productMapper = new ProductMapper($entityManager, $validator);
    }

    public function testCreateProductFromSupplierProductSuccessfully(): void
    {
        $manufacturer = ManufacturerFactory::createOne(['name' => 'Sony']);
        $subcategory = SubcategoryFactory::createOne(['name' => 'Laptops']);

        $supplierProduct = SupplierProductFactory::createOne([
            'name' => 'Test Product',
            'cost' => '100.00',
            'leadTimeDays' => 5,
            'mfrPartNumber' => 'MPN123',
            'weight' => 150,
        ]);

        $product = $this->productMapper->createProductFromSupplierProduct($supplierProduct, $manufacturer, $subcategory);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertSame('Test Product', $product->getName());
        $this->assertSame('100.00', $product->getCost());
        $this->assertSame(5, $product->getLeadTimeDays());
        $this->assertSame('MPN123', $product->getMfrPartNumber());
        $this->assertSame(150, $product->getWeight());
        $this->assertSame($subcategory->getCategory(), $product->getCategory());
        $this->assertSame($subcategory, $product->getSubcategory());
        $this->assertSame($manufacturer, $product->getManufacturer());
    }

    public function testCreateProductFromSupplierWithExistingProduct(): void
    {
        $existingProduct = ProductFactory::createOne(['name' => 'Test Product']);
        $supplierProduct = SupplierProductFactory::createOne([
            'name' => 'Test Product',
            'product' => null
        ]);

        $product = $this->productMapper->createProductFromSupplierProduct(
            $supplierProduct,
            $existingProduct->getManufacturer(),
            $existingProduct->getSubcategory()
        );

        $this->assertInstanceOf(Product::class, $product);
        $this->assertSame('Test Product', $product->getName());
        $this->assertSame($product, $existingProduct);
        $this->assertTrue($product->getSupplierProducts()->contains($supplierProduct));
    }
}