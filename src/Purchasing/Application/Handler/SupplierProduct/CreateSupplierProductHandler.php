<?php

namespace App\Purchasing\Application\Handler\SupplierProduct;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\Product\ProductId;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Purchasing\Application\Command\SupplierProduct\CreateSupplierProduct;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierCategory;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierManufacturer;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierSubcategory;
use App\Purchasing\Domain\Repository\SupplierCategoryRepository;
use App\Purchasing\Domain\Repository\SupplierManufacturerRepository;
use App\Purchasing\Domain\Repository\SupplierProductRepository;
use App\Purchasing\Domain\Repository\SupplierRepository;
use App\Purchasing\Domain\Repository\SupplierSubcategoryRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CreateSupplierProductHandler
{
    public function __construct(
        private SupplierProductRepository $supplierProducts,
        private SupplierRepository $suppliers,
        private SupplierCategoryRepository $supplierCategories,
        private SupplierSubcategoryRepository $supplierSubcategories,
        private SupplierManufacturerRepository $supplierManufacturers,
        private ProductRepository $products,
        private FlusherInterface $flusher,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(CreateSupplierProduct $command): Result
    {
        $supplier = $this->suppliers->get($command->supplierId);
        if (!$supplier instanceof Supplier) {
            return Result::fail('Supplier not found.');
        }

        $supplierCategory = $this->supplierCategories->get($command->supplierCategoryId);
        if (!$supplierCategory instanceof SupplierCategory) {
            return Result::fail('Supplier category not found.');
        }

        $supplierSubcategory = $this->supplierSubcategories->get($command->supplierSubcategoryId);
        if (!$supplierSubcategory instanceof SupplierSubcategory) {
            return Result::fail('Supplier subcategory not found.');
        }

        $supplierManufacturer = $this->supplierManufacturers->get($command->supplierManufacturerId);
        if (!$supplierManufacturer instanceof SupplierManufacturer) {
            return Result::fail('Supplier manufacturer not found.');
        }

        $product = null;
        if (null !== $command->productId) {
            $product = $this->products->get(ProductId::fromint($command->productId));
            if (!$product instanceof Product) {
                return Result::fail('Product not found.');
            }
        }

        $supplierProduct = SupplierProduct::create(
            name: $command->name,
            productCode: $command->productCode,
            supplierCategory: $supplierCategory,
            supplierSubcategory: $supplierSubcategory,
            supplierManufacturer: $supplierManufacturer,
            mfrPartNumber: $command->mfrPartNumber,
            weight: $command->weight,
            supplier: $supplier,
            stock: $command->stock,
            leadTimeDays: $command->leadTimeDays,
            cost: $command->cost,
            product: $product,
            isActive: $command->isActive
        );

        $errors = $this->validator->validate($supplierProduct);
        if (count($errors) > 0) {
            return Result::fail((string) $errors);
        }

        $this->supplierProducts->add($supplierProduct);
        $this->flusher->flush();

        return Result::ok(
            'Supplier product created',
            $supplierProduct->getPublicId()
        );
    }
}
