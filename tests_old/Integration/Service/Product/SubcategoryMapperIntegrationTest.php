<?php

namespace App\Tests\Integration\Service\Product;

use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Service\Product\SubcategoryMapper;
use Doctrine\ORM\EntityManagerInterface;
use tests\Shared\Factory\CategoryFactory;
use tests\Shared\Factory\SupplierCategoryFactory;
use tests\Shared\Factory\SupplierFactory;
use tests\Shared\Factory\SupplierProductFactory;
use tests\Shared\Factory\SupplierSubcategoryFactory;
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
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $this->subcategoryMapper = new SubcategoryMapper($em, $validator);
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
