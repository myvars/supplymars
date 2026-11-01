<?php

declare(strict_types=1);

namespace App\Tests\Purchasing\UI\Console\Setup;

use App\Catalog\Domain\Model\Product\Product;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProductPublicId;
use App\Purchasing\Domain\Repository\SupplierProductRepository;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(StaffUserStory::class)]
final class CreateSupplierProductsCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $command = $application->find('app:create-supplier-products');
        $this->commandTester = new CommandTester($command);
    }

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

        $this->commandTester->execute([]);
        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());

        $display = $this->commandTester->getDisplay();
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

        $this->commandTester->execute([]);

        self::assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
        self::assertStringContainsString('No suppliers found', $this->commandTester->getDisplay());
    }

    private function reload(SupplierProductPublicId $publicId): SupplierProduct
    {
        $supplierProduct = self::getContainer()->get(SupplierProductRepository::class)->getByPublicId($publicId);
        self::assertInstanceOf(SupplierProduct::class, $supplierProduct);

        return $supplierProduct;
    }
}
