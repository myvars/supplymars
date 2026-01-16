<?php

namespace App\Pricing\Application\Handler;

use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Repository\CategoryRepository;
use App\Pricing\Application\Command\UpdateCategoryCost;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class UpdateCategoryCostHandler
{
    public function __construct(
        private CategoryRepository $categories,
        private FlusherInterface $flusher,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(UpdateCategoryCost $command): Result
    {
        $category = $this->categories->getByPublicId($command->id);
        if (!$category instanceof Category) {
            return Result::fail('Category not found.');
        }

        $category->changePricing(
            vatRate: $category->getVatRate(),
            defaultMarkup: $command->defaultMarkup,
            priceModel: $command->priceModel,
            isActive: $command->isActive,
        );

        $errors = $this->validator->validate($category);
        if (count($errors) > 0) {
            return Result::fail((string) $errors);
        }

        $this->flusher->flush();

        return Result::ok('Category cost updated');
    }
}
