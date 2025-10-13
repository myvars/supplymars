<?php

namespace App\Tests\Unit\Service\Product;

use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Catalog\Infrastructure\Persistence\Doctrine\SubcategoryDoctrineRepository;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierSubcategory;
use App\Service\Product\SubcategoryMapper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SubcategoryMapperTest extends TestCase
{
    private MockObject $em;

    private MockObject $validator;

    private SubcategoryMapper $subcategoryMapper;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->subcategoryMapper = new SubcategoryMapper($this->em, $this->validator);
    }

    public function testCreateSubcategoryFromSupplierProductWithMissingSupplierSubcategory(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Supplier subcategory is missing');

        $supplierProduct = $this->createMock(SupplierProduct::class);
        $supplierProduct->method('getSupplierSubcategory')->willReturn(null);

        $category = $this->createMock(Category::class);

        $this->subcategoryMapper->createSubcategoryFromSupplierProduct($supplierProduct, $category);
    }

    public function testCreateSubcategoryWhenSubcategoryExists(): void
    {
        $category = $this->createMock(Category::class);

        $supplierSubcategory = $this->createMock(SupplierSubcategory::class);
        $supplierSubcategory->method('getName')->willReturn('Laptops');
        $supplierSubcategory->method('getMappedSubcategory')->willReturn(null);

        $supplierProduct = $this->createMock(SupplierProduct::class);
        $supplierProduct->method('getSupplierSubcategory')->willReturn($supplierSubcategory);

        $subcategory = $this->createMock(Subcategory::class);
        $subcategory->method('getName')->willReturn('Laptops');
        $subcategory->expects($this->once())->method('addSupplierSubcategory')->with($supplierSubcategory);

        $this->em->method('getRepository')->willReturnMap([
            [Subcategory::class, $this->createMock(SubcategoryDoctrineRepository::class)]
        ]);
        $this->em->getRepository(Subcategory::class)->method('findOneBy')->willReturn($subcategory);

        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $createdSubcategory = $this->subcategoryMapper->createSubcategoryFromSupplierProduct($supplierProduct, $category);

        $this->assertInstanceOf(Subcategory::class, $createdSubcategory);
        $this->assertSame('Laptops', $createdSubcategory->getName());
    }

    public function testCreateSubcategoryFromSupplierProductSuccessfully(): void
    {
        $category = $this->createMock(Category::class);

        $supplierSubcategory = $this->createMock(SupplierSubcategory::class);
        $supplierSubcategory->method('getName')->willReturn('Laptops');
        $supplierSubcategory->method('getMappedSubcategory')->willReturn(null);

        $supplierProduct = $this->createMock(SupplierProduct::class);
        $supplierProduct->method('getSupplierSubcategory')->willReturn($supplierSubcategory);

        $this->em->method('getRepository')->willReturnMap([
            [Subcategory::class, $this->createMock(SubcategoryDoctrineRepository::class)]
        ]);
        $this->em->getRepository(Subcategory::class)->method('findOneBy')->willReturn(null);

        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->em->expects($this->exactly(2))->method('persist');
        $this->em->expects($this->exactly(2))->method('persist');
        $this->em->expects($this->exactly(2))->method('flush');

        $createdSubcategory = $this->subcategoryMapper->createSubcategoryFromSupplierProduct($supplierProduct, $category);

        $this->assertInstanceOf(Subcategory::class, $createdSubcategory);
        $this->assertSame('Laptops', $createdSubcategory->getName());
    }
}
