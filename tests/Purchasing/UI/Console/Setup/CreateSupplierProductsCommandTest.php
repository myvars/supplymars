<?php

declare(strict_types=1);

namespace App\Tests\Purchasing\UI\Console\Setup;

use App\Catalog\Domain\Model\Product\Product;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProductPublicId;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(StaffUserStory::class)]
final class CreateSupplierProductsCommandTest extends KernelTestCase
{
    public function testCreatesOrMapsProductsForActiveUnmappedSupplierProducts(): void
    {
        $supplier = SupplierFactory::createOne();
        $existingProduct = ProductFactory::createOne(['mfrPartNumber' => 'MATCH-0001']);

        $unmapped = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => null,
            'mfrPartNumber' => 'NEW-0001',
        ]);
        $matchingPartNumber = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => null,
            'mfrPartNumber' => 'MATCH-0001',
        ]);
        $alreadyMapped = SupplierProductFactory::createOne(['supplier' => $supplier]);
        $alreadyMappedProductId = $alreadyMapped->getProduct()?->getId();
        $inactive = SupplierProductFactory::createOne([
            'supplier' => $supplier,
            'product' => null,
            'isActive' => false,
        ]);
        $warehouseProduct = SupplierProductFactory::createOne([
            'supplier' => SupplierFactory::new()->asWarehouse()->create(),
            'product' => null,
        ]);

        $result = self::runCommand('app:create-supplier-products');
        $this->assertCommandIsSuccessful($result);

        $display = $result->getDisplay();
        self::assertMatchesRegularExpression('/New products created: 1$/m', $display);
        self::assertMatchesRegularExpression('/Products mapped: 1$/m', $display);
        self::assertMatchesRegularExpression('/Products skipped \(already mapped\): 1$/m', $display);
        self::assertMatchesRegularExpression('/Inactive products skipped: 1$/m', $display);

        self::getContainer()->get(EntityManagerInterface::class)->clear();

        $created = $this->reload($unmapped->getPublicId())->getProduct();
        self::assertInstanceOf(Product::class, $created);
        self::assertSame('NEW-0001', $created->getMfrPartNumber());

        self::assertSame($existingProduct->getId(), $this->reload($matchingPartNumber->getPublicId())->getProduct()?->getId());
        self::assertNotNull($alreadyMappedProductId);
        self::assertSame($alreadyMappedProductId, $this->reload($alreadyMapped->getPublicId())->getProduct()?->getId());
        self::assertNull($this->reload($inactive->getPublicId())->getProduct());
        self::assertNull($this->reload($warehouseProduct->getPublicId())->getProduct());
    }

    public function testFailsWhenThereIsOnlyTheWarehouseSupplier(): void
    {
        SupplierFactory::new()->asWarehouse()->create();

        $result = self::runCommand('app:create-supplier-products');

        $this->assertCommandFailed($result);
        self::assertStringContainsString('No suppliers found', $result->getDisplay());
    }

    private function reload(SupplierProductPublicId $publicId): SupplierProduct
    {
        return SupplierProductFactory::repository()->findOrFail(['publicId' => $publicId->value()]);
    }
}
