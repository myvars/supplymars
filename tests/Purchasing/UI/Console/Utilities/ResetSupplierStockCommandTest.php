<?php

declare(strict_types=1);

namespace App\Tests\Purchasing\UI\Console\Utilities;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\Product\ProductId;
use App\Catalog\Domain\Repository\ProductRepository;
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
final class ResetSupplierStockCommandTest extends KernelTestCase
{
    public function testProductStockFollowsItsActiveSourceAfterReset(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $commandTester = new CommandTester($application->find('app:reset-supplier-stock'));

        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $products = ProductFactory::createMany(5);
        foreach ($products as $product) {
            SupplierProductFactory::createOne([
                'supplier' => $supplier,
                'product' => $product,
                'stock' => 1000,
                'cost' => '10.00',
            ]);
        }

        $commandTester->execute(['supplierId' => (string) $supplier->getId()]);
        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());

        // The command sets stock to 0–300, so every product's stored stock has moved off 1000.
        self::getContainer()->get(EntityManagerInterface::class)->clear();
        $repository = self::getContainer()->get(ProductRepository::class);
        foreach ($products as $product) {
            $reloaded = $repository->get(ProductId::fromInt($product->getId()));
            self::assertInstanceOf(Product::class, $reloaded);

            $source = $reloaded->getSupplierProducts()->first();
            self::assertNotFalse($source);
            self::assertSame($source->getStock(), $reloaded->getStock());
            self::assertNotSame(1000, $reloaded->getStock());
        }
    }
}
