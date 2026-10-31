<?php

declare(strict_types=1);

namespace App\Tests\Purchasing\UI\Console\Utilities;

use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProductPublicId;
use App\Purchasing\Domain\Repository\SupplierProductRepository;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(StaffUserStory::class)]
final class ActivateSupplierProductsCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $command = $application->find('app:activate-supplier-products');
        $this->commandTester = new CommandTester($command);
    }

    public function testActivatesUpToTheGivenNumberOfInactiveSupplierProducts(): void
    {
        SupplierProductFactory::createMany(3, ['isActive' => false]);
        $active = SupplierProductFactory::createOne(['isActive' => true]);

        $this->commandTester->execute(['product-count' => 2]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Processed 2 items.', $this->commandTester->getDisplay());

        self::getContainer()->get(EntityManagerInterface::class)->clear();
        self::assertCount(1, self::getContainer()->get(SupplierProductRepository::class)->findInactive(10));
        self::assertTrue($this->reload($active->getPublicId())->isActive());
    }

    public function testActivatesAnUnmappedSupplierProduct(): void
    {
        // app:create-supplier-products leaves inactive supplier products unmapped.
        $unmapped = SupplierProductFactory::createOne(['isActive' => false, 'product' => null]);

        $this->commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString($unmapped->getProductCode() . ' : unmapped', $this->commandTester->getDisplay());

        self::getContainer()->get(EntityManagerInterface::class)->clear();
        self::assertTrue($this->reload($unmapped->getPublicId())->isActive());
    }

    public function testRejectsACountBelowOne(): void
    {
        $this->commandTester->execute(['product-count' => 0]);

        self::assertSame(Command::INVALID, $this->commandTester->getStatusCode());
        self::assertStringContainsString('productCount must be > 0.', $this->commandTester->getDisplay());
    }

    private function reload(SupplierProductPublicId $publicId): SupplierProduct
    {
        $supplierProduct = self::getContainer()->get(SupplierProductRepository::class)->getByPublicId($publicId);
        self::assertInstanceOf(SupplierProduct::class, $supplierProduct);

        return $supplierProduct;
    }
}
