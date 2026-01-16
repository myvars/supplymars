<?php

namespace App\Tests\Pricing\Application\Handler;

use App\Catalog\Domain\Model\Product\ProductPublicId;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Pricing\Application\Command\UpdateProductCost;
use App\Pricing\Application\Handler\UpdateProductCostHandler;
use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\ProductFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class UpdateProductCostHandlerTest extends KernelTestCase
{
    use Factories;

    private UpdateProductCostHandler $handler;

    private ProductRepository $products;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(UpdateProductCostHandler::class);
        $this->products = self::getContainer()->get(ProductRepository::class);
    }

    public function testHandleUpdatesProductCost(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();

        $command = new UpdateProductCost(
            id: $product->getPublicId(),
            defaultMarkup: '7.500',
            priceModel: PriceModel::PRETTY_99,
            isActive: false,
        );

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('Product cost updated', $result->message);

        $persisted = $this->products->getByPublicId($product->getPublicId());
        self::assertSame('7.500', $persisted->getDefaultMarkup());
        self::assertSame(PriceModel::PRETTY_99, $persisted->getPriceModel());
        self::assertFalse($persisted->isActive());
    }

    public function testFailsWhenProductNotFound(): void
    {
        $missingId = ProductPublicId::new();

        $command = new UpdateProductCost(
            id: $missingId,
            defaultMarkup: '5.000',
            priceModel: PriceModel::DEFAULT,
            isActive: true,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Product not found', $result->message);
    }

    public function testFailsOnNegativeMarkup(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Markup cannot be negative');

        $product = ProductFactory::new()->withActiveSource()->create();

        $command = new UpdateProductCost(
            id: $product->getPublicId(),
            defaultMarkup: '-1.000',
            priceModel: PriceModel::DEFAULT,
            isActive: true,
        );

        ($this->handler)($command);
    }
}
