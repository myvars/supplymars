<?php

declare(strict_types=1);

namespace App\Tests\Purchasing\UI\Console\Utilities;

use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(StaffUserStory::class)]
final class ResetSupplierStockCommandTest extends KernelTestCase
{
    public function testProductStockFollowsItsActiveSourceAfterReset(): void
    {
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

        $result = self::runCommand('app:reset-supplier-stock', ['supplierId' => (string) $supplier->getId()]);
        $this->assertCommandIsSuccessful($result);

        // The command sets stock to 0–300, so every product's stored stock has moved off 1000.
        self::getContainer()->get(EntityManagerInterface::class)->clear();
        foreach ($products as $product) {
            $reloaded = ProductFactory::repository()->findOrFail($product->getId());

            $source = $reloaded->getSupplierProducts()->first();
            self::assertNotFalse($source);
            self::assertSame($source->getStock(), $reloaded->getStock());
            self::assertNotSame(1000, $reloaded->getStock());
        }
    }
}
