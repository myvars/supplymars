<?php

namespace App\Catalog\Application\Handler\Subcategory;

use App\Catalog\Application\Command\Subcategory\CreateSubcategory;
use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Catalog\Domain\Model\Subcategory\SubcategoryId;
use App\Catalog\Domain\Repository\CategoryRepository;
use App\Catalog\Domain\Repository\SubcategoryRepository;
use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Repository\UserRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CreateSubcategoryHandler
{
    public function __construct(
        private SubcategoryRepository $subcategories,
        private CategoryRepository $categories,
        private UserRepository $owners,
        private FlusherInterface $flusher,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(CreateSubcategory $command): Result
    {
        $category = $this->categories->get($command->categoryId);
        if (!$category instanceof Category) {
            return Result::fail('Category not found.');
        }

        $owner = null;
        if (null !== $command->ownerId) {
            $owner = $this->owners->findOneBy(['id' => $command->ownerId, 'isStaff' => true]);
            if (!$owner instanceof User) {
                return Result::fail('Subcategory manager not found.');
            }
        }

        $subcategory = Subcategory::create(
            name: $command->name,
            category: $category,
            owner: $owner,
            defaultMarkup: $command->defaultMarkup,
            priceModel: $command->priceModel,
            isActive: $command->isActive,
        );

        $errors = $this->validator->validate($subcategory);
        if (count($errors) > 0) {
            return Result::fail((string) $errors);
        }

        $this->subcategories->add($subcategory);
        $this->flusher->flush();

        return Result::ok('Subcategory created', SubcategoryId::fromInt($subcategory->getId()));
    }
}
