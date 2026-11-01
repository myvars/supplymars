<?php

declare(strict_types=1);

namespace App\Tests\Purchasing\UI\Console\Setup;

use App\Catalog\Domain\Model\Product\Product;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProductPublicId;
use App\Purchasing\Domain\Repository\SupplierProductRepository;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\VatRateFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(StaffUserStory::class)]
final class CreateWarehouseProductsCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $command = $application->find('app:create-warehouse-products');
        $this->commandTester = new CommandTester($command);
    }

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

        $this->commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Processed 2 supplier products.', $this->commandTester->getDisplay());

        self::getContainer()->get(EntityManagerInterface::class)->clear();
        foreach ($supplierProducts as $supplierProduct) {
            self::assertInstanceOf(Product::class, $this->reload($supplierProduct->getPublicId())->getProduct());
        }

        self::assertNull($this->reload($otherSupplierProduct->getPublicId())->getProduct());
    }

    public function testFailsWithoutAWarehouseSupplier(): void
    {
        SupplierFactory::createOne();

        $this->commandTester->execute([]);

        self::assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
        self::assertStringContainsString('No default supplier found', $this->commandTester->getDisplay());
    }

    private function reload(SupplierProductPublicId $publicId): SupplierProduct
    {
        $supplierProduct = self::getContainer()->get(SupplierProductRepository::class)->getByPublicId($publicId);
        self::assertInstanceOf(SupplierProduct::class, $supplierProduct);

        return $supplierProduct;
    }
}
