<?php

namespace App\Purchasing\UI\Http\Form\Mapper;

use App\Purchasing\Application\Command\SupplierProduct\CreateSupplierProduct;
use App\Purchasing\Domain\Model\Supplier\SupplierId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierCategoryId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierManufacturerId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierSubcategoryId;
use App\Purchasing\UI\Http\Form\Model\SupplierProductForm;

final class CreateSupplierProductMapper
{
    public function __invoke(SupplierProductForm $data): CreateSupplierProduct
    {
        return new CreateSupplierProduct(
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
