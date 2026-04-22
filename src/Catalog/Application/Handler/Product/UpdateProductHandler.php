<?php

declare(strict_types=1);

namespace App\Catalog\Application\Handler\Product;

use App\Catalog\Application\Command\Product\UpdateProduct;
use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Model\Manufacturer\Manufacturer;
use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Catalog\Domain\Repository\CategoryRepository;
use App\Catalog\Domain\Repository\ManufacturerRepository;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Catalog\Domain\Repository\SubcategoryRepository;
use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Model\User\UserId;
use App\Customer\Domain\Repository\UserRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class UpdateProductHandler
{
    public function __construct(
        private ProductRepository $products,
        private CategoryRepository $categories,
        private SubcategoryRepository $subcategories,
        private ManufacturerRepository $manufacturers,
        private UserRepository $owners,
        private MarkupCalculator $markupCalculator,
        private FlusherInterface $flusher,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(UpdateProduct $command): Result
    {
        $product = $this->products->getByPublicId($command->id);
        if (!$product instanceof Product) {
            return Result::fail('Product not found.');
        }

        $category = $this->categories->get($command->categoryId);
        if (!$category instanceof Category) {
            return Result::fail('Category not found.');
        }

        $subcategory = $this->subcategories->get($command->subcategoryId);
        if (!$subcategory instanceof Subcategory) {
            return Result::fail('Subcategory not found.');
        }

        if ($subcategory->getCategory()->getId() !== $category->getId()) {
            return Result::fail('Subcategory does not belong to selected category.');
        }

        $manufacturer = $this->manufacturers->get($command->manufacturerId);
        if (!$manufacturer instanceof Manufacturer) {
            return Result::fail('Manufacturer not found.');
        }

        $owner = null;
        if ($command->ownerId !== null) {
            $owner = $this->owners->getStaffById(UserId::fromInt($command->ownerId));
            if (!$owner instanceof User) {
                return Result::fail('Product manager not found.');
            }
        }

        $product->update(
            markupCalculator: $this->markupCalculator,
            name: $command->name,
            description: $command->description,
            category: $category,
            subcategory: $subcategory,
            manufacturer: $manufacturer,
            mfrPartNumber: $command->mfrPartNumber,
            owner: $owner,
            isActive: $command->isActive,
        );

        $errors = $this->validator->validate($product);
        if (count($errors) > 0) {
            return Result::fail((string) $errors);
        }

        $this->flusher->flush();

        return Result::ok('Product updated');
    }
}
