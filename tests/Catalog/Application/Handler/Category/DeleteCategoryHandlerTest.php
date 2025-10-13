<?php

namespace App\Tests\Catalog\Application\Handler\Category;

use App\Catalog\Application\Command\Category\DeleteCategory;
use App\Catalog\Application\Handler\Category\DeleteCategoryHandler;
use App\Catalog\Domain\Model\Category\CategoryPublicId;
use App\Catalog\Domain\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Tests\Shared\Factory\CategoryFactory;
use Zenstruck\Foundry\Test\Factories;

final class DeleteCategoryHandlerTest extends KernelTestCase
{
    use Factories;

    private DeleteCategoryHandler $handler;
    private CategoryRepository $categories;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(DeleteCategoryHandler::class);
        $this->categories = self::getContainer()->get(CategoryRepository::class);
    }

    public function testDeletesExistingCategory(): void
    {
        $category = CategoryFactory::createOne();
        $publicId = $category->getPublicId();

        $command = new DeleteCategory($publicId);

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('Category deleted', $result->message);
        self::assertNull($this->categories->getByPublicId($publicId));
    }

    public function testFailsWhenCategoryNotFound(): void
    {
        $missingId = CategoryPublicId::new();

        $command = new DeleteCategory($missingId);

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Category not found', $result->message);
    }
}
