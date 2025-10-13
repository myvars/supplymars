<?php

namespace App\Purchasing\UI\Http\Form\Mapper;

use App\Purchasing\Application\Command\SupplierProduct\UpdateSupplierProduct;
use App\Purchasing\Domain\Model\Supplier\SupplierId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierCategoryId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierManufacturerId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProductPublicId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierSubcategoryId;
use App\Purchasing\UI\Http\Form\Model\SupplierProductForm;

final class UpdateSupplierProductMapper
{
    public function __invoke(SupplierProductForm $data): UpdateSupplierProduct
    {
        return new UpdateSupplierProduct(
            SupplierProductPublicId::fromString($data->id),
            $data->name,
            $data->productCode,
            SupplierId::fromInt($data->supplierId),
            SupplierCategoryId::fromInt($data->supplierCategoryId),
            SupplierSubcategoryId::fromInt($data->supplierSubcategoryId),
            SupplierManufacturerId::fromInt($data->supplierManufacturerId),
            $data->mfrPartNumber,
            $data->weight,
            $data->stock,
            $data->leadTimeDays,
            $data->cost,
            $data->productId,
            $data->isActive,
        );
    }
}
