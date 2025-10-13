<?php

namespace App\Tests\Catalog\Application\Handler\Subcategory;

use App\Catalog\Application\Command\Category\CategoryFilter;
use App\Catalog\Application\Handler\Category\CategoryFilterHandler;
use App\Shared\Application\Search\FilterParamBuilder;
use PHPUnit\Framework\TestCase;

final class SubcategoryFilterHandlerTest extends TestCase
{
    public function testBuildsRedirectWithExtraParams(): void
    {
        $params = new FilterParamBuilder();
        $handler = new CategoryFilterHandler($params);

        $command = new CategoryFilter(
            query: 'phone',
            sort: 'name',
            sortDirection: 'DESC',
            page: 3,
            limit: 50,
            priceModel: 'PRETTY_99',
            managerId: 42,
            vatRateId: 5,
        );

        $result = $handler($command);

        self::assertTrue($result->ok);
        $redirect = $result->redirect;
        self::assertSame('app_catalog_category_index', $redirect->route);

        $built = $redirect->params;
        self::assertSame('phone', $built['query']);
        self::assertSame('name', $built['sort']);
        self::assertSame('DESC', $built['sortDirection']);
        self::assertSame(3, $built['page']);
        self::assertSame(50, $built['limit']);
        self::assertSame('PRETTY_99', $built['priceModel']);
        self::assertSame(42, $built['managerId']);
        self::assertSame(5, $built['vatRateId']);
    }
}
