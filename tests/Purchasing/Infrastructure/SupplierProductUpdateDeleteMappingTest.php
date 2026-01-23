<?php

namespace App\Tests\Purchasing\Infrastructure;

use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Tests\Shared\Factory\SupplierCategoryFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierManufacturerFactory;
use App\Tests\Shared\Factory\SupplierSubcategoryFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class SupplierProductUpdateDeleteMappingTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testUpdateRoundTripPersistsChanges(): void
    {
        $supplierA = SupplierFactory::createOne();
        $categoryA = SupplierCategoryFactory::createOne(['supplier' => $supplierA]);
        $subcategoryA = SupplierSubcategoryFactory::createOne([
            'supplier' => $supplierA,
            'supplierCategory' => $categoryA,
        ]);
        $manufacturerA = SupplierManufacturerFactory::createOne(['supplier' => $supplierA]);

        $supplierProduct = SupplierProduct::create(
            name: 'Before',
            productCode: 'CODE-1',
            supplierCategory: $categoryA,
            supplierSubcategory: $subcategoryA,
            supplierManufacturer: $manufacturerA,
            mfrPartNumber: 'MFR-1',
            weight: 100,
            supplier: $supplierA,
            stock: 10,
            leadTimeDays: 5,
            cost: '10.00',
            product: null,
            isActive: true,
        );

        $this->em->persist($supplierProduct);
        $this->em->flush();

        $id = $supplierProduct->getId();

        // Reload and update to new related entities and values
        /** @var SupplierProduct $loaded */
        $loaded = $this->em->getRepository(SupplierProduct::class)->find($id);

        $supplierB = SupplierFactory::createOne();
        $categoryB = SupplierCategoryFactory::createOne(['supplier' => $supplierB]);
        $subcategoryB = SupplierSubcategoryFactory::createOne([
            'supplier' => $supplierB,
            'supplierCategory' => $categoryB,
        ]);
        $manufacturerB = SupplierManufacturerFactory::createOne(['supplier' => $supplierB]);

        $loaded->update(
            name: 'After',
            productCode: 'NEWCODE-999',
            supplierCategory: $categoryB,
            supplierSubcategory: $subcategoryB,
            supplierManufacturer: $manufacturerB,
            mfrPartNumber: 'MFR-2',
            weight: 250,
            supplier: $supplierB,
            stock: 123,
            leadTimeDays: 14,
            cost: '99.99',
            product: null,
            isActive: false,
        );

        $this->em->flush();
        $this->em->clear();

        // Assert updated state after reload
        /** @var SupplierProduct $reloaded */
        $reloaded = $this->em->getRepository(SupplierProduct::class)->find($id);

        self::assertSame('After', $reloaded->getName());
        self::assertSame('NEWCODE-999', $reloaded->getProductCode());
        self::assertSame('MFR-2', $reloaded->getMfrPartNumber());
        self::assertSame(250, $reloaded->getWeight());
        self::assertSame(123, $reloaded->getStock());
        self::assertSame(14, $reloaded->getLeadTimeDays());
        self::assertSame('99.99', $reloaded->getCost());
        self::assertFalse($reloaded->isActive());

        self::assertSame($supplierB->getId(), $reloaded->getSupplier()->getId());
        self::assertSame($categoryB->getId(), $reloaded->getSupplierCategory()?->getId());
        self::assertSame($subcategoryB->getId(), $reloaded->getSupplierSubcategory()?->getId());
        self::assertSame($manufacturerB->getId(), $reloaded->getSupplierManufacturer()?->getId());
        self::assertNull($reloaded->getProduct());
    }

    public function testDeleteRemovesEntity(): void
    {
        $supplier = SupplierFactory::createOne();
        $category = SupplierCategoryFactory::createOne(['supplier' => $supplier]);
        $subcategory = SupplierSubcategoryFactory::createOne([
            'supplier' => $supplier,
            'supplierCategory' => $category,
        ]);
        $manufacturer = SupplierManufacturerFactory::createOne(['supplier' => $supplier]);

        $supplierProduct = SupplierProduct::create(
            name: 'To Delete',
            productCode: 'DEL-001',
            supplierCategory: $category,
            supplierSubcategory: $subcategory,
            supplierManufacturer: $manufacturer,
            mfrPartNumber: 'MFR-DEL',
            weight: 10,
            supplier: $supplier,
            stock: 1,
            leadTimeDays: 1,
            cost: '1.00',
            product: null,
            isActive: true,
        );

        $this->em->persist($supplierProduct);
        $this->em->flush();

        $id = $supplierProduct->getId();

        // Delete and verify removal
        $managed = $this->em->getRepository(SupplierProduct::class)->find($id);
        $this->em->remove($managed);
        $this->em->flush();
        $this->em->clear();

        self::assertNull($this->em->getRepository(SupplierProduct::class)->find($id));
    }
}
