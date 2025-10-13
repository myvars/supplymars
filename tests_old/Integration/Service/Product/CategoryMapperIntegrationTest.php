<?php

namespace App\Tests\Integration\Service\Product;

use App\Catalog\Domain\Model\Category\Category;
use App\Pricing\Domain\Model\VatRate\VatRate;
use App\Service\Product\CategoryMapper;
use Doctrine\ORM\EntityManagerInterface;
use tests\Shared\Factory\SupplierCategoryFactory;
use tests\Shared\Factory\SupplierFactory;
use tests\Shared\Factory\SupplierProductFactory;
use Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class CategoryMapperIntegrationTest extends KernelTestCase
{
    use Factories;

    private CategoryMapper $categoryMapper;

    private EntityManagerInterface $em;

    private Security $security;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $this->security = static::getContainer()->get(Security::class);
        $this->categoryMapper = new CategoryMapper($this->em, $validator, $this->security);
        StaffUserStory::load();
    }

    public function testCreateCategoryFromSupplierProductSuccessfully(): void
    {
        $supplier = SupplierFactory::createOne();
        $supplierCategory = SupplierCategoryFactory::createOne([
            'name' => 'Electronics',
            'supplier' => $supplier
        ]);
        $supplierProduct = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'supplierCategory' => $supplierCategory
        ]);

        $createdCategory = $this->categoryMapper->createCategoryFromSupplierProduct($supplierProduct);

        $this->assertInstanceOf(Category::class, $createdCategory);
        $this->assertSame('Electronics', $createdCategory->getName());
        $this->assertSame($this->security->getUser(), $createdCategory->getOwner());
        $this->assertSame(
            $this->em->getRepository(VatRate::class)->findDefaultVatRate(),
            $createdCategory->getVatRate()
        );
    }
}
