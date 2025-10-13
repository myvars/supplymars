<?php

namespace App\Tests\Purchasing\Application\Handler\SupplierProduct;

use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Model\Manufacturer\Manufacturer;
use App\Catalog\Domain\Model\Product\Product;
use App\Purchasing\Application\Service\SupplierProductMappingService;
use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\ManufacturerFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SubcategoryFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\VatRateFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

final class SupplierProductMappingServiceTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $em;
    private ValidatorInterface $validator;
    private SupplierProductMappingService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
        $this->service = self::getContainer()->get(SupplierProductMappingService::class);
        VatRateFactory::new()->withStandardRate()->create();
    }

    #[WithStory(StaffUserStory::class)]
    public function testMapCreatesAllEntitiesAndMappings(): void
    {
        $sp = SupplierProductFactory::createOne(['product' => null]);
        $sm = $sp->getSupplierManufacturer();
        $sc = $sp->getSupplierCategory();
        $ss = $sp->getSupplierSubcategory();

        $product = ($this->service)->map($sp);
        $this->em->flush();

        self::assertInstanceOf(Product::class, $product);
        self::assertSame($sp->getName(), $product->getName());

        self::assertSame($sm->getName(), $product->getManufacturer()->getName());
        self::assertSame($sc->getName(), $product->getCategory()->getName());
        self::assertSame($ss->getName(), $product->getSubcategory()->getName());

        self::assertSame($product, $sp->getProduct());
        self::assertSame($product->getManufacturer(), $sm->getMappedManufacturer());
        self::assertSame($product->getSubcategory(), $ss->getMappedSubcategory());
    }

    public function testMapReusesExistingEntitiesAndDoesNotDuplicate(): void
    {
        $sp = SupplierProductFactory::createOne(['product' => null]);
        $sm = $sp->getSupplierManufacturer();
        $sc = $sp->getSupplierCategory();
        $ss = $sp->getSupplierSubcategory();

        $existingManufacturer = ManufacturerFactory::createOne(['name' => $sm->getName()]);
        $existingCategory = CategoryFactory::createOne(['name' => $sc->getName()]);
        $existingSubcategory = SubcategoryFactory::CreateOne([
            'name' => $ss->getName(),
            'category' => $existingCategory,
        ]);
        $existingProduct = ProductFactory::createOne([
            'name' => $sp->getName(),
            'category' => $existingCategory,
            'subcategory' => $existingSubcategory,
            'manufacturer' => $existingManufacturer,
        ]);

        // map and ensure reuse
        $product = ($this->service)->map($sp);
        $this->em->flush();

        self::assertSame($existingProduct->getId(), $product->getId());
        self::assertSame($existingProduct, $sp->getProduct());

        $products = $this->em->getRepository(Product::class)->findBy(['name' => $sp->getName()]);
        $manufacturers = $this->em->getRepository(Manufacturer::class)->findBy(['name' => $sm->getName()]);
        $categories = $this->em->getRepository(Category::class)->findBy(['name' => $sc->getName()]);

        self::assertCount(1, $products);
        self::assertCount(1, $manufacturers);
        self::assertCount(1, $categories);
    }

    public function testMapThrowsWhenSupplierManufacturerMissing(): void
    {
        $sp = SupplierProductFactory::createOne(['product' => null]);
        $sp->assignSupplierManufacturer(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Supplier manufacturer is missing');
        ($this->service)->map($sp);
    }

    public function testMapThrowsWhenSupplierCategoryMissing(): void
    {
        $sp = SupplierProductFactory::createOne(['product' => null]);
        $sp->assignSupplierCategory(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Supplier category is missing');
        ($this->service)->map($sp);
    }

    public function testMapThrowsWhenSupplierSubcategoryMissing(): void
    {
        $sp = SupplierProductFactory::createOne(['product' => null]);
        $sp->assignSupplierSubcategory(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Supplier subcategory is missing');
        ($this->service)->map($sp);
    }
}
