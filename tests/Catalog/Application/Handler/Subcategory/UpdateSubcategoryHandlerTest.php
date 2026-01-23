<?php

namespace App\Tests\Catalog\Application\Handler\Subcategory;

use App\Catalog\Application\Command\Subcategory\UpdateSubcategory;
use App\Catalog\Application\Handler\Subcategory\UpdateSubcategoryHandler;
use App\Catalog\Domain\Model\Category\CategoryId;
use App\Catalog\Domain\Model\Subcategory\SubcategoryPublicId;
use App\Catalog\Domain\Repository\SubcategoryRepository;
use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\SubcategoryFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class UpdateSubcategoryHandlerTest extends KernelTestCase
{
    use Factories;

    private UpdateSubcategoryHandler $handler;

    private SubcategoryRepository $subcategories;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(UpdateSubcategoryHandler::class);
        $this->subcategories = self::getContainer()->get(SubcategoryRepository::class);
    }

    public function testHandleUpdatesSubcategory(): void
    {
        $subcategory = SubcategoryFactory::new()->create();
        $newCategory = CategoryFactory::new()->create();
        $newOwner = UserFactory::new()->asStaff()->create();

        $command = new UpdateSubcategory(
            id: $subcategory->getPublicId(),
            name: 'Updated Subcategory',
            categoryId: CategoryId::fromInt($newCategory->getId()),
            defaultMarkup: '7.500',
            priceModel: PriceModel::PRETTY_99,
            ownerId: $newOwner->getId(),
            isActive: false,
        );

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        $persisted = $this->subcategories->getByPublicId($subcategory->getPublicId());
        self::assertSame('Updated Subcategory', $persisted->getName());
        self::assertSame($newCategory->getId(), $persisted->getCategory()->getId());
        self::assertSame($newOwner->getId(), $persisted->getOwner()?->getId());
        self::assertSame('7.500', $persisted->getDefaultMarkup());
        self::assertSame(PriceModel::PRETTY_99, $persisted->getPriceModel());
        self::assertFalse($persisted->isActive());
    }

    public function testFailsWhenSubcategoryNotFound(): void
    {
        $newCategory = CategoryFactory::new()->create();
        $newOwner = UserFactory::new()->asStaff()->create();
        $missingId = SubcategoryPublicId::new();

        $command = new UpdateSubcategory(
            id: $missingId,
            name: 'X',
            categoryId: CategoryId::fromInt($newCategory->getId()),
            defaultMarkup: '7.500',
            priceModel: PriceModel::PRETTY_99,
            ownerId: $newOwner->getId(),
            isActive: false,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Subcategory not found', $result->message);
    }

    public function testHandleFailsWhenCategoryMissing(): void
    {
        $subcategory = SubcategoryFactory::new()->create();
        $newOwner = UserFactory::new()->asStaff()->create();

        $command = new UpdateSubcategory(
            id: $subcategory->getPublicId(),
            name: 'Bad',
            categoryId: CategoryId::fromInt(999999),
            defaultMarkup: '5.000',
            priceModel: PriceModel::NONE,
            ownerId: $newOwner->getId(),
            isActive: true,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Category not found', $result->message);
    }

    public function testHandleFailsWhenOwnerNotStaff(): void
    {
        $subcategory = SubcategoryFactory::new()->create();
        $newCategory = CategoryFactory::new()->create();
        $newNonStaff = UserFactory::new()->create();

        $command = new UpdateSubcategory(
            id: $subcategory->getPublicId(),
            name: 'Bad',
            categoryId: CategoryId::fromInt($newCategory->getId()),
            defaultMarkup: '5.000',
            priceModel: PriceModel::NONE,
            ownerId: $newNonStaff->getId(),
            isActive: true,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Subcategory manager not found', $result->message);
    }
}
