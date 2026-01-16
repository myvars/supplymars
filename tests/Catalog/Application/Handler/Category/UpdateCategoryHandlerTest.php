<?php

namespace App\Tests\Catalog\Application\Handler\Category;

use App\Catalog\Application\Command\Category\UpdateCategory;
use App\Catalog\Application\Handler\Category\UpdateCategoryHandler;
use App\Catalog\Domain\Model\Category\CategoryPublicId;
use App\Catalog\Domain\Repository\CategoryRepository;
use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Factory\VatRateFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class UpdateCategoryHandlerTest extends KernelTestCase
{
    use Factories;

    private UpdateCategoryHandler $handler;

    private CategoryRepository $categories;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(UpdateCategoryHandler::class);
        $this->categories = self::getContainer()->get(CategoryRepository::class);
    }

    public function testHandleUpdatesCategory(): void
    {
        $category = CategoryFactory::createOne();
        $newVatRate = VatRateFactory::createOne();
        $newOwner = UserFactory::new()->asStaff()->create();

        $command = new UpdateCategory(
            id: $category->getPublicId(),
            name: 'Updated Name',
            vatRateId: $newVatRate->getId(),
            defaultMarkup: '7.500',
            priceModel: PriceModel::PRETTY_99,
            ownerId: $newOwner->getId(),
            isActive: false,
        );

        $result = ($this->handler)($command);

        $this->assertTrue($result->ok);

        $persisted = $this->categories->getByPublicId($category->getPublicId());
        self::assertSame('Updated Name', $persisted->getName());
        self::assertSame('7.500', $persisted->getDefaultMarkup());
        self::assertSame(PriceModel::PRETTY_99, $persisted->getPriceModel());
        self::assertSame($newOwner->getId(), $persisted->getOwner()->getId());
        self::assertSame($newVatRate->getId(), $persisted->getVatRate()->getId());
        self::assertFalse($persisted->isActive());
    }

    public function testFailsWhenCategoryNotFound(): void
    {
        $newVatRate = VatRateFactory::createOne();
        $newOwner = UserFactory::new()->asStaff()->create();

        $missingId = CategoryPublicId::new();

        $command = new UpdateCategory(
            id: $missingId,
            name: 'X',
            vatRateId: $newVatRate->getId(),
            defaultMarkup: '5.000',
            priceModel: PriceModel::DEFAULT,
            ownerId: $newOwner->getId(),
            isActive: true,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Category not found', $result->message);
    }

    public function testFailsWhenVatRateMissing(): void
    {
        $category = CategoryFactory::createOne();

        $newOwner = UserFactory::new()->asStaff()->create();

        $command = new UpdateCategory(
            id: $category->getPublicId(),
            name: 'X',
            vatRateId: 999999,
            defaultMarkup: '5.000',
            priceModel: PriceModel::DEFAULT,
            ownerId: $newOwner->getId(),
            isActive: true,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('VAT rate not found', $result->message);
    }

    public function testFailsWhenOwnerNotStaff(): void
    {
        $category = CategoryFactory::createOne();
        $newVatRate = VatRateFactory::createOne();
        $newNonStaff = UserFactory::new()->create();

        $command = new UpdateCategory(
            id: $category->getPublicId(),
            name: 'X',
            vatRateId: $newVatRate->getId(),
            defaultMarkup: '5.000',
            priceModel: PriceModel::DEFAULT,
            ownerId: $newNonStaff->getId(),
            isActive: true,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Category manager not found', $result->message);
    }
}
