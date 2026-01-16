<?php

namespace App\Tests\Pricing\Application\Handler;

use App\Catalog\Domain\Model\Category\CategoryPublicId;
use App\Catalog\Domain\Repository\CategoryRepository;
use App\Pricing\Application\Command\UpdateCategoryCost;
use App\Pricing\Application\Handler\UpdateCategoryCostHandler;
use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\CategoryFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class UpdateCategoryCostHandlerTest extends KernelTestCase
{
    use Factories;

    private UpdateCategoryCostHandler $handler;

    private CategoryRepository $categories;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(UpdateCategoryCostHandler::class);
        $this->categories = self::getContainer()->get(CategoryRepository::class);
    }

    public function testHandleUpdatesCategoryCost(): void
    {
        $category = CategoryFactory::createOne();

        $command = new UpdateCategoryCost(
            id: $category->getPublicId(),
            defaultMarkup: '7.500',
            priceModel: PriceModel::PRETTY_99,
            isActive: false,
        );

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('Category cost updated', $result->message);

        $persisted = $this->categories->getByPublicId($category->getPublicId());
        self::assertSame('7.500', $persisted->getDefaultMarkup());
        self::assertSame(PriceModel::PRETTY_99, $persisted->getPriceModel());
        self::assertFalse($persisted->isActive());
    }

    public function testFailsWhenCategoryNotFound(): void
    {
        $missingId = CategoryPublicId::new();

        $command = new UpdateCategoryCost(
            id: $missingId,
            defaultMarkup: '5.000',
            priceModel: PriceModel::DEFAULT,
            isActive: true,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Category not found', $result->message);
    }

    public function testFailsOnNegativeMarkup(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Markup cannot be negative');

        $category = CategoryFactory::createOne();

        $command = new UpdateCategoryCost(
            id: $category->getPublicId(),
            defaultMarkup: '-1.000',
            priceModel: PriceModel::NONE,
            isActive: true,
        );

        ($this->handler)($command);
    }

    public function testFailsOnMissingPriceModel(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A category must have a price model');

        $category = CategoryFactory::createOne();

        $command = new UpdateCategoryCost(
            id: $category->getPublicId(),
            defaultMarkup: '5.000',
            priceModel: PriceModel::NONE,
            isActive: true,
        );

        ($this->handler)($command);
    }
}
