<?php

namespace App\Tests\Catalog\Application\Handler\Product;

use App\Catalog\Application\Command\Product\ProductFilter;
use App\Catalog\Application\Handler\Product\ProductFilterHandler;
use App\Shared\Application\Search\FilterParamBuilder;
use PHPUnit\Framework\TestCase;

final class ProductFilterHandlerTest extends TestCase
{
    public function testBuildsRedirectWithExtraParams(): void
    {
        $params = new FilterParamBuilder();
        $handler = new ProductFilterHandler($params);

        $command = new ProductFilter(
            query: 'laptop',
            sort: 'name',
            sortDirection: 'ASC',
            page: 2,
            limit: 25,
            mfrPartNumber: 'ABC-123',
            categoryId: 10,
            subcategoryId: 101,
            manufacturerId: 7,
            inStock: true,
        );

        $result = $handler($command);

        self::assertTrue($result->ok);
        $redirect = $result->redirect;
        self::assertSame('app_catalog_product_index', $redirect->route);

        $built = $redirect->params;
        self::assertSame('laptop', $built['query']);
        self::assertSame('name', $built['sort']);
        self::assertSame('ASC', $built['sortDirection']);
        self::assertSame(2, $built['page']);
        self::assertSame(25, $built['limit']);
        self::assertSame('ABC-123', $built['mfrPartNumber']);
        self::assertSame(10, $built['categoryId']);
        self::assertSame(101, $built['subcategoryId']);
        self::assertSame(7, $built['manufacturerId']);
        self::assertTrue($built['inStock']);
    }
}
