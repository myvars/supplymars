<?php

namespace App\Purchasing\UI\Http\Form\Mapper;

use App\Purchasing\Application\Command\SupplierProduct\SupplierProductFilter;
use App\Purchasing\Application\Search\SupplierProductSearchCriteria;

final class SupplierProductFilterMapper
{
    public function __invoke(SupplierProductSearchCriteria $criteria): SupplierProductFilter
    {
        return new SupplierProductFilter(
            $criteria->getQuery(),
            $criteria->getSort(),
            $criteria->getSortDirection(),
            $criteria->getPage(),
            $criteria->getLimit(),
            $criteria->supplierId,
            $criteria->productCode,
            $criteria->supplierCategoryId,
            $criteria->supplierSubcategoryId,
            $criteria->supplierManufacturerId,
            $criteria->inStock,
            $criteria->isActive,
        );
    }
}
