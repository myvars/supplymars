<?php

declare(strict_types=1);

namespace App\Tests\Purchasing\UI\Console\Utilities;

use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProductPublicId;
use App\Purchasing\Domain\Repository\SupplierProductRepository;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(StaffUserStory::class)]
final class ActivateSupplierProductsCommandTest extends KernelTestCase
{
    public function testActivatesUpToTheGivenNumberOfInactiveSupplierProducts(): void
    {
        SupplierProductFactory::createMany(3, ['isActive' => false]);
        $active = SupplierProductFactory::createOne(['isActive' => true]);

        $result = self::runCommand('app:activate-supplier-products', ['product-count' => 2]);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('Processed 2 items.', $result->getDisplay());

        self::getContainer()->get(EntityManagerInterface::class)->clear();
        self::assertCount(1, self::getContainer()->get(SupplierProductRepository::class)->findInactive(10));
        self::assertTrue($this->reload($active->getPublicId())->isActive());
    }

    public function testActivatesAnUnmappedSupplierProduct(): void
    {
        // app:create-supplier-products leaves inactive supplier products unmapped.
        $unmapped = SupplierProductFactory::createOne(['isActive' => false, 'product' => null]);

        $result = self::runCommand('app:activate-supplier-products', [], verbosity: OutputInterface::VERBOSITY_VERBOSE);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString($unmapped->getProductCode() . ' : unmapped', $result->getDisplay());

        self::getContainer()->get(EntityManagerInterface::class)->clear();
        self::assertTrue($this->reload($unmapped->getPublicId())->isActive());
    }

    public function testRejectsACountBelowOne(): void
    {
        $result = self::runCommand('app:activate-supplier-products', ['product-count' => 0]);

        $this->assertCommandIsInvalid($result);
        self::assertStringContainsString('productCount must be > 0.', $result->getDisplay());
    }

    private function reload(SupplierProductPublicId $publicId): SupplierProduct
    {
        return SupplierProductFactory::repository()->findOrFail(['publicId' => $publicId->value()]);
    }
}
