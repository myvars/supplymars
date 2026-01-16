<?php

namespace App\Tests\Purchasing\Application\Handler\SupplierProduct;

use App\Purchasing\Application\Command\SupplierProduct\UpdateSupplierProduct;
use App\Purchasing\Application\Handler\SupplierProduct\UpdateSupplierProductHandler;
use App\Purchasing\Domain\Model\Supplier\SupplierId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierCategoryId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierManufacturerId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProductPublicId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierSubcategoryId;
use App\Purchasing\Domain\Repository\SupplierProductRepository;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierCategoryFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierManufacturerFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\SupplierSubcategoryFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class UpdateSupplierProductHandlerTest extends KernelTestCase
{
    use Factories;

    private UpdateSupplierProductHandler $handler;

    private SupplierProductRepository $supplierProducts;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(UpdateSupplierProductHandler::class);
        $this->supplierProducts = self::getContainer()->get(SupplierProductRepository::class);
    }

    public function testHandleUpdatesSupplierProduct(): void
    {
        $supplierProduct = SupplierProductFactory::createOne();

        $supplier = SupplierFactory::createOne();
        $category = SupplierCategoryFactory::createOne(['supplier' => $supplier]);
        $subcategory = SupplierSubcategoryFactory::createOne([
            'supplier' => $supplier,
            'supplierCategory' => $category,
        ]);
        $manufacturer = SupplierManufacturerFactory::createOne(['supplier' => $supplier]);
        $product = ProductFactory::createOne();

        $command = new UpdateSupplierProduct(
            id: $supplierProduct->getPublicId(),
            name: 'Updated Supplier Product',
            productCode: 'NEWCODE-999',
            supplierId: SupplierId::fromInt($supplier->getId()),
            supplierCategoryId: SupplierCategoryId::fromInt($category->getId()),
            supplierSubcategoryId: SupplierSubcategoryId::fromInt($subcategory->getId()),
            supplierManufacturerId: SupplierManufacturerId::fromInt($manufacturer->getId()),
            mfrPartNumber: 'MFR-UPDATED-001',
            weight: 250,
            stock: 123,
            leadTimeDays: 14,
            cost: '123.45',
            productId: $product->getId(),
            isActive: false,
        );

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);

        $persisted = $this->supplierProducts->getByPublicId($supplierProduct->getPublicId());
        self::assertSame('Updated Supplier Product', $persisted->getName());
        self::assertSame('NEWCODE-999', $persisted->getProductCode());
        self::assertSame($supplier->getId(), $persisted->getSupplier()->getId());
        self::assertSame($category->getId(), $persisted->getSupplierCategory()->getId());
        self::assertSame($subcategory->getId(), $persisted->getSupplierSubcategory()->getId());
        self::assertSame($manufacturer->getId(), $persisted->getSupplierManufacturer()->getId());
        self::assertSame('MFR-UPDATED-001', $persisted->getMfrPartNumber());
        self::assertSame(250, $persisted->getWeight());
        self::assertSame(123, $persisted->getStock());
        self::assertSame(14, $persisted->getLeadTimeDays());
        self::assertSame('123.45', $persisted->getCost());
        self::assertSame($product->getId(), $persisted->getProduct()->getId());
        self::assertFalse($persisted->isActive());
    }

    public function testFailsWhenSupplierProductNotFound(): void
    {
        $missingId = SupplierProductPublicId::new();

        $supplier = SupplierFactory::createOne();
        $category = SupplierCategoryFactory::createOne(['supplier' => $supplier]);
        $subcategory = SupplierSubcategoryFactory::createOne([
            'supplier' => $supplier,
            'supplierCategory' => $category,
        ]);
        $manufacturer = SupplierManufacturerFactory::createOne(['supplier' => $supplier]);

        $command = new UpdateSupplierProduct(
            id: $missingId,
            name: 'X',
            productCode: 'CODE',
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
            isActive: true,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Supplier product not found', $result->message);
    }

    public function testFailsWhenSupplierMissing(): void
    {
        $supplierProduct = SupplierProductFactory::createOne();
        $category = SupplierCategoryFactory::createOne();
        $subcategory = SupplierSubcategoryFactory::createOne([
            'supplierCategory' => $category,
            'supplier' => $category->getSupplier(),
        ]);
        $manufacturer = SupplierManufacturerFactory::createOne(['supplier' => $category->getSupplier()]);

        $command = new UpdateSupplierProduct(
            id: $supplierProduct->getPublicId(),
            name: 'X',
            productCode: 'CODE',
            supplierId: SupplierId::fromInt(999999),
            supplierCategoryId: SupplierCategoryId::fromInt($category->getId()),
            supplierSubcategoryId: SupplierSubcategoryId::fromInt($subcategory->getId()),
            supplierManufacturerId: SupplierManufacturerId::fromInt($manufacturer->getId()),
            mfrPartNumber: 'MFR',
            weight: 1,
            stock: 1,
            leadTimeDays: 1,
            cost: '1.00',
            productId: null,
            isActive: true,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Supplier not found', $result->message);
    }

    public function testFailsWhenCategoryMissing(): void
    {
        $supplierProduct = SupplierProductFactory::createOne();
        $supplier = SupplierFactory::createOne();
        $subcategory = SupplierSubcategoryFactory::createOne(['supplier' => $supplier]);
        $manufacturer = SupplierManufacturerFactory::createOne(['supplier' => $supplier]);

        $command = new UpdateSupplierProduct(
            id: $supplierProduct->getPublicId(),
            name: 'X',
            productCode: 'CODE',
            supplierId: SupplierId::fromInt($supplier->getId()),
            supplierCategoryId: SupplierCategoryId::fromInt(999999),
            supplierSubcategoryId: SupplierSubcategoryId::fromInt($subcategory->getId()),
            supplierManufacturerId: SupplierManufacturerId::fromInt($manufacturer->getId()),
            mfrPartNumber: 'MFR',
            weight: 1,
            stock: 1,
            leadTimeDays: 1,
            cost: '1.00',
            productId: null,
            isActive: true,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Supplier category not found', $result->message);
    }

    public function testFailsWhenSubcategoryMissing(): void
    {
        $supplierProduct = SupplierProductFactory::createOne();
        $supplier = SupplierFactory::createOne();
        $category = SupplierCategoryFactory::createOne(['supplier' => $supplier]);
        $manufacturer = SupplierManufacturerFactory::createOne(['supplier' => $supplier]);

        $command = new UpdateSupplierProduct(
            id: $supplierProduct->getPublicId(),
            name: 'X',
            productCode: 'CODE',
            supplierId: SupplierId::fromInt($supplier->getId()),
            supplierCategoryId: SupplierCategoryId::fromInt($category->getId()),
            supplierSubcategoryId: SupplierSubcategoryId::fromInt(999999),
            supplierManufacturerId: SupplierManufacturerId::fromInt($manufacturer->getId()),
            mfrPartNumber: 'MFR',
            weight: 1,
            stock: 1,
            leadTimeDays: 1,
            cost: '1.00',
            productId: null,
            isActive: true,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Supplier subcategory not found', $result->message);
    }

    public function testFailsWhenManufacturerMissing(): void
    {
        $supplierProduct = SupplierProductFactory::createOne();
        $supplier = SupplierFactory::createOne();
        $category = SupplierCategoryFactory::createOne(['supplier' => $supplier]);
        $subcategory = SupplierSubcategoryFactory::createOne([
            'supplier' => $supplier,
            'supplierCategory' => $category,
        ]);

        $command = new UpdateSupplierProduct(
            id: $supplierProduct->getPublicId(),
            name: 'X',
            productCode: 'CODE',
            supplierId: SupplierId::fromInt($supplier->getId()),
            supplierCategoryId: SupplierCategoryId::fromInt($category->getId()),
            supplierSubcategoryId: SupplierSubcategoryId::fromInt($subcategory->getId()),
            supplierManufacturerId: SupplierManufacturerId::fromInt(999999),
            mfrPartNumber: 'MFR',
            weight: 1,
            stock: 1,
            leadTimeDays: 1,
            cost: '1.00',
            productId: null,
            isActive: true,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Supplier manufacturer not found', $result->message);
    }

    public function testFailsWhenProductMissing(): void
    {
        $supplierProduct = SupplierProductFactory::createOne();
        $supplier = SupplierFactory::createOne();
        $category = SupplierCategoryFactory::createOne(['supplier' => $supplier]);
        $subcategory = SupplierSubcategoryFactory::createOne([
            'supplier' => $supplier,
            'supplierCategory' => $category,
        ]);
        $manufacturer = SupplierManufacturerFactory::createOne(['supplier' => $supplier]);

        $command = new UpdateSupplierProduct(
            id: $supplierProduct->getPublicId(),
            name: 'X',
            productCode: 'CODE',
            supplierId: SupplierId::fromInt($supplier->getId()),
            supplierCategoryId: SupplierCategoryId::fromInt($category->getId()),
            supplierSubcategoryId: SupplierSubcategoryId::fromInt($subcategory->getId()),
            supplierManufacturerId: SupplierManufacturerId::fromInt($manufacturer->getId()),
            mfrPartNumber: 'MFR',
            weight: 1,
            stock: 1,
            leadTimeDays: 1,
            cost: '1.00',
            productId: 999999,
            isActive: true,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Product not found', $result->message);
    }
}
