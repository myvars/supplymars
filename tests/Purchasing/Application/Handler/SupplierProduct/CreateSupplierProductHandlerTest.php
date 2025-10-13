<?php

namespace App\Tests\Purchasing\Application\Handler\SupplierProduct;

use App\Purchasing\Application\Command\SupplierProduct\CreateSupplierProduct;
use App\Purchasing\Application\Handler\SupplierProduct\CreateSupplierProductHandler;
use App\Purchasing\Domain\Model\Supplier\SupplierId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierCategoryId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierManufacturerId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProductId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierSubcategoryId;
use App\Purchasing\Domain\Repository\SupplierProductRepository;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierCategoryFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierManufacturerFactory;
use App\Tests\Shared\Factory\SupplierSubcategoryFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class CreateSupplierProductHandlerTest extends KernelTestCase
{
    use Factories;

    private CreateSupplierProductHandler $handler;
    private SupplierProductRepository $supplierProducts;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(CreateSupplierProductHandler::class);
        $this->supplierProducts = self::getContainer()->get(SupplierProductRepository::class);
    }

    public function testHandleCreatesSupplierProduct(): void
    {
        $supplier = SupplierFactory::createOne();
        $category = SupplierCategoryFactory::createOne(['supplier' => $supplier]);
        $subcategory = SupplierSubcategoryFactory::createOne([
            'supplier' => $supplier,
            'supplierCategory' => $category,
        ]);
        $manufacturer = SupplierManufacturerFactory::createOne(['supplier' => $supplier]);
        $product = ProductFactory::createOne();

        $command = new CreateSupplierProduct(
            name: 'New Supplier Product',
            productCode: 'CODE123',
            supplierId: SupplierId::fromInt($supplier->getId()),
            supplierCategoryId: SupplierCategoryId::fromInt($category->getId()),
            supplierSubcategoryId: SupplierSubcategoryId::fromInt($subcategory->getId()),
            supplierManufacturerId: SupplierManufacturerId::fromInt($manufacturer->getId()),
            mfrPartNumber: 'MFR-999',
            weight: 100,
            stock: 50,
            leadTimeDays: 7,
            cost: '15.25',
            productId: $product->getId(),
            isActive: true
        );

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertInstanceOf(SupplierProductId::class, $result->payload);
        $persisted = $this->supplierProducts->get($result->payload);
        self::assertSame('New Supplier Product', $persisted->getName());
        self::assertSame('CODE123', $persisted->getProductCode());
        self::assertTrue($persisted->isActive());
    }

    public function testHandleFailsWhenSupplierMissing(): void
    {
        $supplierId = SupplierId::fromInt(999999); // non-existent
        $category = SupplierCategoryFactory::createOne();
        $subcategory = SupplierSubcategoryFactory::createOne([
            'supplierCategory' => $category,
            'supplier' => $category->getSupplier()
        ]);
        $manufacturer = SupplierManufacturerFactory::createOne(['supplier' => $category->getSupplier()]);

        $command = new CreateSupplierProduct(
            name: 'Bad',
            productCode: 'BAD',
            supplierId: $supplierId,
            supplierCategoryId: SupplierCategoryId::fromInt($category->getId()),
            supplierSubcategoryId: SupplierSubcategoryId::fromInt($subcategory->getId()),
            supplierManufacturerId: SupplierManufacturerId::fromInt($manufacturer->getId()),
            mfrPartNumber: 'MFR',
            weight: 10,
            stock: 5,
            leadTimeDays: 1,
            cost: '1.00',
            productId: null,
            isActive: true
        );

        $result = ($this->handler)($command);
        self::assertFalse($result->ok);
        self::assertStringContainsString('Supplier not found', $result->message);
    }

    public function testHandleFailsWhenProductMissing(): void
    {
        $supplier = SupplierFactory::createOne();
        $category = SupplierCategoryFactory::createOne(['supplier' => $supplier]);
        $subcategory = SupplierSubcategoryFactory::createOne([
            'supplier' => $supplier,
            'supplierCategory' => $category,
        ]);
        $manufacturer = SupplierManufacturerFactory::createOne(['supplier' => $supplier]);

        $command = new CreateSupplierProduct(
            name: 'No Product',
            productCode: 'NP',
            supplierId: SupplierId::fromInt($supplier->getId()),
            supplierCategoryId: SupplierCategoryId::fromInt($category->getId()),
            supplierSubcategoryId: SupplierSubcategoryId::fromInt($subcategory->getId()),
            supplierManufacturerId: SupplierManufacturerId::fromInt($manufacturer->getId()),
            mfrPartNumber: 'MFR',
            weight: 1,
            stock: 1,
            leadTimeDays: 1,
            cost: '2.00',
            productId: 999999,
            isActive: true
        );

        $result = ($this->handler)($command);
        self::assertFalse($result->ok);
        self::assertStringContainsString('Product not found', $result->message);
    }

    public function testHandleThrowsOnEmptyName(): void
    {
        $supplier = SupplierFactory::createOne();
        $category = SupplierCategoryFactory::createOne(['supplier' => $supplier]);
        $subcategory = SupplierSubcategoryFactory::createOne([
            'supplier' => $supplier,
            'supplierCategory' => $category,
        ]);
        $manufacturer = SupplierManufacturerFactory::createOne(['supplier' => $supplier]);

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Product name cannot be empty');

        $command = new CreateSupplierProduct(
            name: '',
            productCode: 'X',
            supplierId: SupplierId::fromInt($supplier->getId()),
            supplierCategoryId: SupplierCategoryId::fromInt($category->getId()),
            supplierSubcategoryId: SupplierSubcategoryId::fromInt($subcategory->getId()),
            supplierManufacturerId: SupplierManufacturerId::fromInt($manufacturer->getId()),
            mfrPartNumber: 'MFR',
            weight: 1,
            stock: 1,
            leadTimeDays: 1,
            cost: '1.00',
            productId: null,
            isActive: true
        );

        ($this->handler)($command);
    }
}
