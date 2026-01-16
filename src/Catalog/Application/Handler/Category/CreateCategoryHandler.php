<?php

namespace App\Catalog\Application\Handler\Category;

use App\Catalog\Application\Command\Category\CreateCategory;
use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Model\Category\CategoryId;
use App\Catalog\Domain\Repository\CategoryRepository;
use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Repository\UserRepository;
use App\Pricing\Domain\Model\VatRate\VatRate;
use App\Pricing\Domain\Model\VatRate\VatRateId;
use App\Pricing\Domain\Repository\VatRateRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CreateCategoryHandler
{
    public function __construct(
        private CategoryRepository $categories,
        private VatRateRepository $vatRates,
        private UserRepository $owners,
        private FlusherInterface $flusher,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(CreateCategory $command): Result
    {
        $vatRate = $this->vatRates->get(VatRateId::fromInt($command->vatRateId));
        if (!$vatRate instanceof VatRate) {
            return Result::fail('VAT rate not found.');
        }

        $owner = $this->owners->findOneBy(['id' => $command->ownerId, 'isStaff' => true]);
        if (!$owner instanceof User) {
            return Result::fail('Category manager not found.');
        }

        $category = Category::create(
            name: $command->name,
            owner: $owner,
            vatRate: $vatRate,
            defaultMarkup: $command->defaultMarkup,
            priceModel: $command->priceModel,
            isActive: $command->isActive,
        );

        $errors = $this->validator->validate($category);
        if (count($errors) > 0) {
            return Result::fail((string) $errors);
        }

        $this->categories->add($category);
        $this->flusher->flush();

        return Result::ok('Category created', CategoryId::fromInt($category->getId()));
    }
}
