<?php

namespace App\Tests\Catalog\Application\Handler\Subcategory;

use App\Catalog\Application\Command\Subcategory\CreateSubcategory;
use App\Catalog\Application\Handler\Subcategory\CreateSubcategoryHandler;
use App\Catalog\Domain\Model\Category\CategoryId;
use App\Catalog\Domain\Repository\SubcategoryRepository;
use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class CreateSubcategoryHandlerTest extends KernelTestCase
{
    use Factories;

    private CreateSubcategoryHandler $handler;

    private SubcategoryRepository $subcategories;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(CreateSubcategoryHandler::class);
        $this->subcategories = self::getContainer()->get(SubcategoryRepository::class);
    }

    public function testHandleCreatesSubcategory(): void
    {
        $category = CategoryFactory::new()->create();
        $owner = UserFactory::new()->asStaff()->create();

        $command = new CreateSubcategory(
            name: 'New Subcategory',
            categoryId: CategoryId::fromInt($category->getId()),
            defaultMarkup: '5.00',
            priceModel: PriceModel::NONE,
            ownerId: $owner->getId(),
            isActive: true,
        );

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        $subcategoryId = $result->payload;
        $persisted = $this->subcategories->get($subcategoryId);

        self::assertSame('New Subcategory', $persisted->getName());
        self::assertSame($category->getId(), $persisted->getCategory()?->getId());
        self::assertSame($owner->getId(), $persisted->getOwner()?->getId());
        self::assertSame(PriceModel::NONE, $persisted->getPriceModel());
        self::assertTrue($persisted->isActive());
    }

    public function testHandleFailsWhenCategoryMissing(): void
    {
        $owner = UserFactory::new()->asStaff()->create();

        $command = new CreateSubcategory(
            name: 'Bad',
            categoryId: CategoryId::fromInt(999_999),
            defaultMarkup: '5.00',
            priceModel: PriceModel::NONE,
            ownerId: $owner->getId(),
            isActive: true,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Category not found', $result->message);
    }

    public function testHandleFailsWhenOwnerNotStaff(): void
    {
        $category = CategoryFactory::new()->create();
        $nonStaff = UserFactory::new()->create();

        $command = new CreateSubcategory(
            name: 'Bad',
            categoryId: CategoryId::fromInt($category->getId()),
            defaultMarkup: 5,
            priceModel: PriceModel::NONE,
            ownerId: $nonStaff->getId(),
            isActive: true,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Subcategory manager not found', $result->message);
    }
}
