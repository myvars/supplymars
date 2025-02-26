<?php

namespace App\Tests\Unit\Service\Product;

use App\Entity\Category;
use App\Entity\Manufacturer;
use App\Entity\Product;
use App\Entity\Subcategory;
use App\Entity\SupplierProduct;
use App\Repository\ProductRepository;
use App\Service\Product\ProductMapper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductMapperTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;
    private ProductMapper $productMapper;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->productMapper = new ProductMapper($this->entityManager, $this->validator);
    }

    public function testCreateProductFromSupplierProductWithExistingProduct(): void
    {
        $supplierProduct = $this->createMock(SupplierProduct::class);
        $supplierProduct->method('getName')->willReturn('Test Product');

        $existingProduct = $this->createMock(Product::class);

        $this->entityManager->method('getRepository')->willReturnMap([
            [Product::class, $this->createMock(ProductRepository::class)]
        ]);
        $this->entityManager->getRepository(Product::class)->method('findOneBy')->willReturn($existingProduct);

        $manufacturer = $this->createMock(Manufacturer::class);
        $subcategory = $this->createMock(Subcategory::class);

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $product = $this->productMapper->createProductFromSupplierProduct($supplierProduct, $manufacturer, $subcategory);

        $this->assertSame($existingProduct, $product);
    }

    public function testCreateProductFromSupplierProductSuccessfully(): void
    {
        $supplierProduct = $this->createMock(SupplierProduct::class);
        $supplierProduct->method('getName')->willReturn('Test Product');
        $supplierProduct->method('getCost')->willReturn('100.00');
        $supplierProduct->method('getLeadTimeDays')->willReturn(5);
        $supplierProduct->method('getMfrPartNumber')->willReturn('MPN123');
        $supplierProduct->method('getWeight')->willReturn(150);

        $manufacturer = $this->createMock(Manufacturer::class);
        $subcategory = $this->createMock(Subcategory::class);
        $subcategory->method('getCategory')->willReturn($this->createMock(Category::class));

        $this->entityManager->method('getRepository')->willReturnMap([
            [Product::class, $this->createMock(ProductRepository::class)]
        ]);
        $this->entityManager->getRepository(Product::class)->method('findOneBy')->willReturn(null);

        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->entityManager->expects($this->exactly(2))->method('persist');
        $this->entityManager->expects($this->exactly(2))->method('flush');

        $product = $this->productMapper->createProductFromSupplierProduct($supplierProduct, $manufacturer, $subcategory);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertSame('Test Product', $product->getName());
    }
}