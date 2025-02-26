<?php

namespace App\Tests\Integration\Service\Product;

use App\Entity\Category;
use App\Entity\VatRate;
use App\Factory\SupplierCategoryFactory;
use App\Factory\SupplierFactory;
use App\Factory\SupplierProductFactory;
use App\Service\Product\CategoryMapper;
use App\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class ManufacturerMapperIntegrationTest extends KernelTestCase
{
    use Factories;

    private CategoryMapper $categoryMapper;
    private EntityManagerInterface $entityManager;
    private Security $security;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $this->security = static::getContainer()->get(Security::class);
        $this->categoryMapper = new CategoryMapper($this->entityManager, $validator, $this->security);
        StaffUserStory::load();
    }

    public function testCreateCategoryFromSupplierProductSuccessfully(): void
    {
        $supplier = SupplierFactory::createOne()->_real();
        $supplierCategory = SupplierCategoryFactory::createOne([
            'name' => 'Electronics',
            'supplier' => $supplier
        ]);
        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'supplierCategory' => $supplierCategory
        ]);

        $createdCategory = $this->categoryMapper->createCategoryFromSupplierProduct($supplierProduct->_real());

        $this->assertInstanceOf(Category::class, $createdCategory);
        $this->assertSame('Electronics', $createdCategory->getName());
        $this->assertSame($this->security->getUser(), $createdCategory->getOwner());
        $this->assertSame(
            $this->entityManager->getRepository(VatRate::class)->findDefaultVatRate(),
            $createdCategory->getVatRate()
        );
    }
}