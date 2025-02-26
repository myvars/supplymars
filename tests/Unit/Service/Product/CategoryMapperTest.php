<?php

namespace App\Tests\Unit\Service\Product;

use App\Entity\Category;
use App\Entity\SupplierCategory;
use App\Entity\SupplierProduct;
use App\Entity\User;
use App\Entity\VatRate;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use App\Repository\VatRateRepository;
use App\Service\Product\CategoryMapper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CategoryMapperTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;
    private Security $security;
    private CategoryMapper $categoryMapper;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->categoryMapper = new CategoryMapper($this->entityManager, $this->validator, $this->security);
    }

    public function testCreateCategoryFromSupplierProductWithMissingSupplierCategory(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Supplier category is missing');

        $supplierProduct = $this->createMock(SupplierProduct::class);
        $supplierProduct->method('getSupplierCategory')->willReturn(null);

        $this->categoryMapper->createCategoryFromSupplierProduct($supplierProduct);
    }

    public function testCreateCategoryFromSupplierProductSuccessfully(): void
    {
        $supplierCategory = $this->createMock(SupplierCategory::class);
        $supplierCategory->method('getName')->willReturn('Electronics');

        $supplierProduct = $this->createMock(SupplierProduct::class);
        $supplierProduct->method('getSupplierCategory')->willReturn($supplierCategory);

        $category = $this->createMock(Category::class);
        $category->method('getName')->willReturn('Electronics');

        $user = $this->createMock(User::class);
        $vatRate = $this->createMock(VatRate::class);

        $this->entityManager->method('getRepository')->willReturnMap([
            [Category::class, $this->createMock(CategoryRepository::class)],
            [User::class, $this->createMock(UserRepository::class)],
            [VatRate::class, $this->createMock(VatRateRepository::class)]
        ]);
        $this->entityManager->getRepository(Category::class)->method('findOneBy')->willReturn(null);
        $this->entityManager->getRepository(User::class)->method('findOneBy')->willReturn($user);
        $this->entityManager->getRepository(VatRate::class)->method('findOneBy')->willReturn($vatRate);

        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $this->security->method('getUser')->willReturn($user);

        $createdCategory = $this->categoryMapper->createCategoryFromSupplierProduct($supplierProduct);

        $this->assertInstanceOf(Category::class, $createdCategory);
        $this->assertSame('Electronics', $createdCategory->getName());
    }
}