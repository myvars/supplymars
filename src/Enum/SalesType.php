<?php

namespace App\Enum;

enum SalesType: string
{
    case PRODUCT = 'product';
    case CATEGORY = 'category';
    case SUBCATEGORY = 'subcategory';
    case MANUFACTURER = 'manufacturer';
    case SUPPLIER = 'supplier';
}