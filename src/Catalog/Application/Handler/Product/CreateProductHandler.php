<?php

namespace App\Catalog\Application\Handler\Product;

use App\Catalog\Application\Command\Product\CreateProduct;
use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Model\Manufacturer\Manufacturer;
use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\Product\ProductId;
use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Catalog\Domain\Repository\CategoryRepository;
use App\Catalog\Domain\Repository\ManufacturerRepository;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Catalog\Domain\Repository\SubcategoryRepository;
use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Repository\UserRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CreateProductHandler
{
    public function __construct(
        private ProductRepository $products,
        private CategoryRepository $categories,
        private SubcategoryRepository $subcategories,
        private ManufacturerRepository $manufacturers,
        private UserRepository $owners,
        private FlusherInterface $flusher,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(CreateProduct $command): Result
    {
        $category = $this->categories->get($command->categoryId);
        if (!$category instanceof Category) {
            return Result::fail('Category not found.');
        }

        $subcategory = $this->subcategories->get($command->subcategoryId);
        if (!$subcategory instanceof Subcategory) {
            return Result::fail('Subcategory not found.');
        }

        $manufacturer = $this->manufacturers->get($command->manufacturerId);
        if (!$manufacturer instanceof Manufacturer) {
            return Result::fail('Manufacturer not found.');
        }

        $owner = null;
        if (null !== $command->ownerId) {
            $owner = $this->owners->findOneBy(['id' => $command->ownerId, 'isStaff' => true]);
            if (!$owner instanceof User) {
                return Result::fail('Product manager not found.');
            }
        }

        $product = Product::create(
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

        $this->products->add($product);
        $this->flusher->flush();

        return Result::ok('Product created', ProductId::fromInt($product->getId()));
    }
}
