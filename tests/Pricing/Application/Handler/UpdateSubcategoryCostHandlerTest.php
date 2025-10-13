<?php

namespace App\Tests\Pricing\Application\Handler;

use App\Catalog\Domain\Model\Subcategory\SubcategoryPublicId;
use App\Catalog\Domain\Repository\SubcategoryRepository;
use App\Pricing\Application\Command\UpdateSubcategoryCost;
use App\Pricing\Application\Handler\UpdateSubcategoryCostHandler;
use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\SubcategoryFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class UpdateSubcategoryCostHandlerTest extends KernelTestCase
{
    use Factories;

    private UpdateSubcategoryCostHandler $handler;
    private SubcategoryRepository $subcategories;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(UpdateSubcategoryCostHandler::class);
        $this->subcategories = self::getContainer()->get(SubcategoryRepository::class);
    }

    public function testHandleUpdatesSubcategoryCost(): void
    {
        $subcategory = SubcategoryFactory::createOne();

        $command = new UpdateSubcategoryCost(
            id: $subcategory->getPublicId(),
            defaultMarkup: '7.500',
            priceModel: PriceModel::PRETTY_99,
            isActive: false,
        );

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('Subcategory cost updated', $result->message);

        $persisted = $this->subcategories->getByPublicId($subcategory->getPublicId());
        self::assertSame('7.500', $persisted->getDefaultMarkup());
        self::assertSame(PriceModel::PRETTY_99, $persisted->getPriceModel());
        self::assertFalse($persisted->isActive());
    }

    public function testFailsWhenSubcategoryNotFound(): void
    {
        $missingId = SubcategoryPublicId::new();

        $command = new UpdateSubcategoryCost(
            id: $missingId,
            defaultMarkup: '5.000',
            priceModel: PriceModel::NONE,
            isActive: true,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Subcategory not found', $result->message);
    }

    public function testFailsOnNegativeMarkup(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Markup cannot be negative');

        $subcategory = SubcategoryFactory::createOne();

        $command = new UpdateSubcategoryCost(
            id: $subcategory->getPublicId(),
            defaultMarkup: '-1.000',
            priceModel: PriceModel::NONE,
            isActive: true,
        );

        ($this->handler)($command);
    }
}
