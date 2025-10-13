<?php

namespace App\Tests\Unit\Service\Product;

use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Infrastructure\Persistence\Doctrine\CategoryDoctrineRepository;
use App\Customer\Domain\Model\User\User;
use App\Customer\Infrastructure\Persistence\Doctrine\UserDoctrineRepository;
use App\Pricing\Domain\Model\VatRate\VatRate;
use App\Pricing\Infrastructure\Persistence\Doctrine\VatRateDoctrineRepository;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierCategory;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Service\Product\CategoryMapper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CategoryMapperTest extends TestCase
{
    private MockObject $em;

    private MockObject $validator;

    private MockObject $security;

    private CategoryMapper $categoryMapper;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->categoryMapper = new CategoryMapper($this->em, $this->validator, $this->security);
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

        $this->em->method('getRepository')->willReturnMap([
            [Category::class, $this->createMock(CategoryDoctrineRepository::class)],
            [User::class, $this->createMock(UserDoctrineRepository::class)],
            [VatRate::class, $this->createMock(VatRateDoctrineRepository::class)]
        ]);
        $this->em->getRepository(Category::class)->method('findOneBy')->willReturn(null);
        $this->em->getRepository(User::class)->method('findOneBy')->willReturn($user);
        $this->em->getRepository(VatRate::class)->method('findOneBy')->willReturn($vatRate);

        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $this->security->method('getUser')->willReturn($user);

        $createdCategory = $this->categoryMapper->createCategoryFromSupplierProduct($supplierProduct);

        $this->assertInstanceOf(Category::class, $createdCategory);
        $this->assertSame('Electronics', $createdCategory->getName());
    }
}
