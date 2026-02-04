<?php

namespace App\Purchasing\Application\Handler\SupplierProduct;

use App\Purchasing\Application\Command\SupplierProduct\SupplierProductFilter;
use App\Shared\Application\RedirectTarget;
use App\Shared\Application\Result;
use App\Shared\Application\Search\FilterParamBuilder;

final readonly class SupplierProductFilterHandler
{
    private const string ROUTE = 'app_purchasing_supplier_product_index';

    public function __construct(private FilterParamBuilder $params)
    {
    }

    public function __invoke(SupplierProductFilter $command): Result
    {
        $extras = [
            'supplierId' => $command->supplierId,
            'productCode' => $command->productCode,
            'supplierCategoryId' => $command->supplierCategoryId,
            'supplierSubcategoryId' => $command->supplierSubcategoryId,
            'supplierManufacturerId' => $command->supplierManufacturerId,
            'inStock' => $command->inStock,
            'isActive' => $command->isActive,
        ];

        return Result::ok(
            redirect: new RedirectTarget(
                route: self::ROUTE,
                params: $this->params->build($command, $extras),
            )
        );
    }
}
