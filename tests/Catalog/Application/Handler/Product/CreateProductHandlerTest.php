<?php

namespace App\Tests\Catalog\Application\Handler\Product;

use App\Catalog\Application\Command\Product\CreateProduct;
use App\Catalog\Application\Handler\Product\CreateProductHandler;
use App\Catalog\Domain\Model\Category\CategoryId;
use App\Catalog\Domain\Model\Manufacturer\ManufacturerId;
use App\Catalog\Domain\Model\Subcategory\SubcategoryId;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\ManufacturerFactory;
use App\Tests\Shared\Factory\SubcategoryFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class CreateProductHandlerTest extends KernelTestCase
{
    use Factories;

    private CreateProductHandler $handler;

    private ProductRepository $products;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(CreateProductHandler::class);
        $this->products = self::getContainer()->get(ProductRepository::class);
    }

    public function testHandleCreatesProduct(): void
    {
        $category = CategoryFactory::createOne();
        $subcategory = SubcategoryFactory::createOne(['category' => $category]);
        $manufacturer = ManufacturerFactory::createOne();
        $owner = UserFactory::new()->asStaff()->create();

        $command = new CreateProduct(
            name: 'New Product',
            description: 'New product description',
            categoryId: CategoryId::fromInt($category->getId()),
            subcategoryId: SubcategoryId::fromInt($subcategory->getId()),
            manufacturerId: ManufacturerId::fromInt($manufacturer->getId()),
            mfrPartNumber: 'MFR-0001',
            ownerId: $owner->getId(),
            isActive: true,
        );

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);

        $productId = $result->payload;
        $persisted = $this->products->get($productId);

        self::assertSame('New Product', $persisted->getName());
        self::assertSame('MFR-0001', $persisted->getMfrPartNumber());
        self::assertTrue($persisted->isActive());
    }

    public function testHandleFailsWhenSubcategoryMissing(): void
    {
        $category = CategoryFactory::createOne();
        $manufacturer = ManufacturerFactory::createOne();
        $owner = UserFactory::new()->asStaff()->create();

        $command = new CreateProduct(
            name: 'Bad Product',
            description: null,
            categoryId: CategoryId::fromInt($category->getId()),
            subcategoryId: SubcategoryId::fromInt(999999),
            manufacturerId: ManufacturerId::fromInt($manufacturer->getId()),
            mfrPartNumber: 'MFR-0002',
            ownerId: $owner->getId(),
            isActive: true,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Subcategory not found', $result->message);
    }

    public function testHandleFailsWhenOwnerNotStaff(): void
    {
        $category = CategoryFactory::createOne();
        $subcategory = SubcategoryFactory::createOne(['category' => $category]);
        $manufacturer = ManufacturerFactory::createOne();
        $nonStaff = UserFactory::new()->create();

        $command = new CreateProduct(
            name: 'Bad Product',
            description: null,
            categoryId: CategoryId::fromInt($category->getId()),
            subcategoryId: SubcategoryId::fromInt($subcategory->getId()),
            manufacturerId: ManufacturerId::fromInt($manufacturer->getId()),
            mfrPartNumber: 'MFR-0003',
            ownerId: $nonStaff->getId(),
            isActive: true,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Product manager not found', $result->message);
    }
}
