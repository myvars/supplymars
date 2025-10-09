<?php

namespace App\Tests\Integration\Service\Product;

use App\Entity\Subcategory;
use App\Factory\CategoryFactory;
use App\Factory\SupplierCategoryFactory;
use App\Factory\SupplierFactory;
use App\Factory\SupplierProductFactory;
use App\Factory\SupplierSubcategoryFactory;
use App\Service\Product\SubcategoryMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class SubcategoryMapperIntegrationTest extends KernelTestCase
{
    use Factories;

    private SubcategoryMapper $subcategoryMapper;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $this->subcategoryMapper = new SubcategoryMapper($entityManager, $validator);
    }

    public function testCreateSubcategoryFromSupplierProductSuccessfully(): void
    {
        $supplier = SupplierFactory::createOne();
        $supplierCategory = SupplierCategoryFactory::createOne([
            'name' => 'Electronics',
            'supplier' => $supplier
        ]);
        $supplierSubcategory = SupplierSubcategoryFactory::createOne([
            'name' => 'Laptops',
            'supplier' => $supplier,
            'supplierCategory' => $supplierCategory
        ]);
        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'supplierCategory' => $supplierCategory,
            'supplierSubcategory' => $supplierSubcategory
        ]);

        $category = CategoryFactory::createOne(['name' => 'Electronics']);

        $createdSubcategory = $this->subcategoryMapper->createSubcategoryFromSupplierProduct($supplierProduct, $category);

        $this->assertInstanceOf(Subcategory::class, $createdSubcategory);
        $this->assertSame('Laptops', $createdSubcategory->getName());
        $this->assertSame($category, $createdSubcategory->getCategory());
    }
}