<?php

namespace App\Catalog\Application\Handler\Category;

use App\Catalog\Application\Command\Category\DeleteCategory;
use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Repository\CategoryRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;

final readonly class DeleteCategoryHandler
{
    public function __construct(
        private CategoryRepository $categories,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(DeleteCategory $command): Result
    {
        $category = $this->categories->getByPublicId($command->id);
        if (!$category instanceof Category) {
            return Result::fail('Category not found.');
        }

        $this->categories->remove($category);
        $this->flusher->flush();

        return Result::ok(message: 'Category deleted');
    }
}
