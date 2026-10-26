<?php

declare(strict_types=1);

namespace App\Tests\Pricing\UI\Console\Utilities;

use App\Catalog\Domain\Model\Product\Product;
use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Test\Factories;

final class ResyncProductPricingCommandTest extends KernelTestCase
{
    use Factories;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $this->commandTester = new CommandTester($application->find('app:resync-product-pricing'));
    }

    public function testBringsStaleProductBackInStepWithItsSources(): void
    {
        $product = $this->staleProduct();

        $this->commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('1 changed', $this->commandTester->getDisplay());

        // Markup 25%, VAT 20%, no rounding: 20.00 × 1.25 × 1.20 = 30.00
        $reloaded = $this->reload($product);
        self::assertSame('20.00', $reloaded->getCost());
        self::assertSame(40, $reloaded->getStock());
        self::assertSame('30.00', $reloaded->getSellPriceIncVat());
    }

    public function testSecondRunChangesNothing(): void
    {
        $this->staleProduct();

        $this->commandTester->execute([]);
        $this->commandTester->execute([]);

        self::assertStringContainsString('0 changed', $this->commandTester->getDisplay());
    }

    public function testDryRunReportsWithoutSaving(): void
    {
        $product = $this->staleProduct();

        $this->commandTester->execute(['--dry-run' => true]);

        self::assertStringContainsString('1 would change', $this->commandTester->getDisplay());
        $reloaded = $this->reload($product);
        self::assertSame('10.00', $reloaded->getCost());
        self::assertSame(5, $reloaded->getStock());
    }

    public function testResyncsAcrossBatchBoundaries(): void
    {
        $products = [$this->staleProduct(), $this->staleProduct(), $this->staleProduct()];

        $this->commandTester->execute(['--batch-size' => 1]);

        self::assertStringContainsString('3 changed', $this->commandTester->getDisplay());
        foreach ($products as $product) {
            self::assertSame('20.00', $this->reload($product)->getCost());
        }
    }

    public function testProductThatFailsIsLeftAsItWasAndReported(): void
    {
        $product = $this->staleProduct();
        self::getContainer()->get(Connection::class)->executeStatement(
            'UPDATE vat_rate SET rate = -1 WHERE id = :id',
            ['id' => $product->getCategory()->getVatRate()->getId()],
        );

        $this->commandTester->execute([]);

        self::assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
        self::assertStringContainsString('VAT rate must not be negative', $this->commandTester->getDisplay());
        self::assertStringContainsString('1 failed', $this->commandTester->getDisplay());
        self::assertSame('10.00', $this->reload($product)->getCost());
    }

    public function testClearsSourceThatRanOutOfStock(): void
    {
        $product = ProductFactory::createOne();
        $source = SupplierProductFactory::createOne(['product' => $product, 'cost' => '10.00', 'stock' => 5]);
        self::getContainer()->get(Connection::class)->executeStatement(
            'UPDATE supplier_product SET stock = 0 WHERE id = :id',
            ['id' => $source->getId()],
        );
        self::getContainer()->get(EntityManagerInterface::class)->clear();

        $this->commandTester->execute(['-v' => true], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        self::assertStringContainsString('source none, stock 0', $this->commandTester->getDisplay());
        $reloaded = $this->reload($product);
        self::assertNull($reloaded->getActiveProductSource());
        self::assertSame(0, $reloaded->getStock());
        self::assertSame('10.00', $reloaded->getCost());
    }

    /**
     * A product priced from its source at cost 10.00 and stock 5, whose source then changed in the
     * database without the product following, as simulated changes did before #106.
     */
    private function staleProduct(): Product
    {
        $category = CategoryFactory::createOne(['defaultMarkup' => '25.000', 'priceModel' => PriceModel::DEFAULT]);
        $product = ProductFactory::createOne(['category' => $category]);
        $source = SupplierProductFactory::createOne(['product' => $product, 'cost' => '10.00', 'stock' => 5]);

        self::getContainer()->get(Connection::class)->executeStatement(
            'UPDATE supplier_product SET cost = :cost, stock = :stock WHERE id = :id',
            ['cost' => '20.00', 'stock' => 40, 'id' => $source->getId()],
        );
        self::getContainer()->get(EntityManagerInterface::class)->clear();

        return $product;
    }

    private function reload(Product $product): Product
    {
        self::getContainer()->get(EntityManagerInterface::class)->clear();

        $reloaded = ProductFactory::repository()->find($product->getId());
        self::assertInstanceOf(Product::class, $reloaded);

        return $reloaded;
    }
}
