<?php

declare(strict_types=1);

namespace App\Tests\Purchasing\UI\Console\Setup;

use App\Catalog\Domain\Model\Product\Product;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProductPublicId;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\VatRateFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(StaffUserStory::class)]
final class CreateWarehouseProductsCommandTest extends KernelTestCase
{
    public function testCreatesAProductForEachWarehouseSupplierProduct(): void
    {
        VatRateFactory::new()->withStandardRate()->create();
        $warehouse = SupplierFactory::new()->asWarehouse()->create();
        $supplierProducts = SupplierProductFactory::createMany(2, [
            'supplier' => $warehouse,
            'product' => null,
        ]);
        $otherSupplierProduct = SupplierProductFactory::createOne([
            'supplier' => SupplierFactory::createOne(),
            'product' => null,
        ]);

        $result = self::runCommand('app:create-warehouse-products');

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('Processed 2 supplier products.', $result->getDisplay());

        self::getContainer()->get(EntityManagerInterface::class)->clear();
        foreach ($supplierProducts as $supplierProduct) {
            self::assertInstanceOf(Product::class, $this->reload($supplierProduct->getPublicId())->getProduct());
        }

        self::assertNull($this->reload($otherSupplierProduct->getPublicId())->getProduct());
    }

    public function testFailsWithoutAWarehouseSupplier(): void
    {
        SupplierFactory::createOne();

        $result = self::runCommand('app:create-warehouse-products');

        $this->assertCommandFailed($result);
        self::assertStringContainsString('No default supplier found', $result->getDisplay());
    }

    private function reload(SupplierProductPublicId $publicId): SupplierProduct
    {
        return SupplierProductFactory::repository()->findOrFail(['publicId' => $publicId->value()]);
    }
}
