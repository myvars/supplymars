<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Metric;

enum SalesType: string
{
    case PRODUCT = 'product';
    case CATEGORY = 'category';
    case SUBCATEGORY = 'subcategory';
    case MANUFACTURER = 'manufacturer';
    case SUPPLIER = 'supplier';
    case ALL = 'all';
}
