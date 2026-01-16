<?php

namespace App\Tests\Unit\Service\Product;

use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Model\Manufacturer\Manufacturer;
use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Catalog\Infrastructure\Persistence\Doctrine\ProductDoctrineRepository;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Service\Product\ProductMapper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductMapperTest extends TestCase
{
    private MockObject $em;

    private MockObject $validator;

    private ProductMapper $productMapper;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->productMapper = new ProductMapper($this->em, $this->validator);
    }

    public function testCreateProductFromSupplierProductWithExistingProduct(): void
    {
        $supplierProduct = $this->createMock(SupplierProduct::class);
        $supplierProduct->method('getName')->willReturn('Test Product');

        $existingProduct = $this->createMock(Product::class);

        $this->em->method('getRepository')->willReturnMap([
            [Product::class, $this->createMock(ProductDoctrineRepository::class)],
        ]);
        $this->em->getRepository(Product::class)->method('findOneBy')->willReturn($existingProduct);

        $manufacturer = $this->createMock(Manufacturer::class);
        $subcategory = $this->createMock(Subcategory::class);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

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

        $this->em->method('getRepository')->willReturnMap([
            [Product::class, $this->createMock(ProductDoctrineRepository::class)],
        ]);
        $this->em->getRepository(Product::class)->method('findOneBy')->willReturn(null);

        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->em->expects($this->exactly(2))->method('persist');
        $this->em->expects($this->exactly(2))->method('flush');

        $product = $this->productMapper->createProductFromSupplierProduct($supplierProduct, $manufacturer, $subcategory);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertSame('Test Product', $product->getName());
    }
}
