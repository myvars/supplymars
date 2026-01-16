<?php

namespace App\Tests\Catalog\Application\Handler\Category;

use App\Catalog\Application\Command\Category\CreateCategory;
use App\Catalog\Application\Handler\Category\CreateCategoryHandler;
use App\Catalog\Domain\Repository\CategoryRepository;
use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Factory\VatRateFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class CreateCategoryHandlerTest extends KernelTestCase
{
    use Factories;

    private CreateCategoryHandler $handler;

    private CategoryRepository $categories;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(CreateCategoryHandler::class);
        $this->categories = self::getContainer()->get(CategoryRepository::class);
    }

    public function testHandleCreatesCategory(): void
    {
        $vatRate = VatRateFactory::createOne();
        $owner = UserFactory::new()->asStaff()->create();

        $command = new CreateCategory(
            name: 'New Category',
            vatRateId: $vatRate->getId(),
            defaultMarkup: '5.000',
            priceModel: PriceModel::DEFAULT,
            ownerId: $owner->getId(),
            isActive: true,
        );

        $result = ($this->handler)($command);

        $this->assertTrue($result->ok);
        $categoryId = $result->payload;
        $persisted = $this->categories->get($categoryId);
        self::assertSame('New Category', $persisted->getName());
    }

    public function testHandleFailsWhenVatRateMissing(): void
    {
        $owner = UserFactory::new()->asStaff()->create();

        $command = new CreateCategory(
            name: 'Bad',
            vatRateId: 999999,
            defaultMarkup: '5.000',
            priceModel: PriceModel::DEFAULT,
            ownerId: $owner->getId(),
            isActive: true,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('VAT rate not found', $result->message);
    }

    public function testHandleFailsWhenOwnerNotStaff(): void
    {
        $vatRate = VatRateFactory::createOne();
        $nonStaff = UserFactory::new()->create(); // not asStaff()

        $command = new CreateCategory(
            name: 'Bad',
            vatRateId: $vatRate->getId(),
            defaultMarkup: '5.000',
            priceModel: PriceModel::DEFAULT,
            ownerId: $nonStaff->getId(),
            isActive: true,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Category manager not found', $result->message);
    }
}
