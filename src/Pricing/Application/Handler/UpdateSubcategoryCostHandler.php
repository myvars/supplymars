<?php

namespace App\Pricing\Application\Handler;

use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Catalog\Domain\Repository\SubcategoryRepository;
use App\Pricing\Application\Command\UpdateSubcategoryCost;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class UpdateSubcategoryCostHandler
{
    public function __construct(
        private SubcategoryRepository $subcategories,
        private FlusherInterface $flusher,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(UpdateSubcategoryCost $command): Result
    {
        $subcategory = $this->subcategories->getByPublicId($command->id);
        if (!$subcategory instanceof Subcategory) {
            return Result::fail('Subcategory not found.');
        }

        $subcategory->changePricing(
            defaultMarkup: $command->defaultMarkup,
            priceModel: $command->priceModel,
            isActive: $command->isActive,
        );

        $errors = $this->validator->validate($subcategory);
        if (count($errors) > 0) {
            return Result::fail((string) $errors);
        }

        $this->flusher->flush();

        return Result::ok('Subcategory cost updated');
    }
}
