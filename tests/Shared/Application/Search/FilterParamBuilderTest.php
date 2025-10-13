<?php

namespace App\Tests\Shared\Application\Search;

use App\Shared\Application\Search\FilterCommand;
use App\Shared\Application\Search\FilterParamBuilder;
use PHPUnit\Framework\TestCase;

final class FilterParamBuilderTest extends TestCase
{
    private FilterParamBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new FilterParamBuilder();
    }

    public function testBaseBuildsFromSourceAndCleans(): void
    {
        $criteria = new FilterCommand(
            query: null,
            sort: 'id',
            sortDirection: 'ASC',
            page: 2,
            limit: 25
        );

        $params = $this->builder->base($criteria);

        self::assertSame([
            'sort' => 'id',
            'sortDirection' => 'ASC',
            'page' => 2,
            'limit' => 25,
        ], $params);
        self::assertArrayNotHasKey('query', $params);
    }

    public function testBuildAddsFilterFlagWhenExtrasPresentAndFlagTrue(): void
    {
        $criteria = new FilterCommand(
            query: 'abc',
            sort: 'id',
            sortDirection: 'DESC',
            page: 1,
            limit: 5
        );

        $params = $this->builder->build($criteria, ['brand' => 'Nike']);

        self::assertSame([
            'query' => 'abc',
            'sort' => 'id',
            'sortDirection' => 'DESC',
            'page' => 1,
            'limit' => 5,
            'brand' => 'Nike',
            'filter' => 'on',
        ], $params);
    }

    public function testBuildDoesNotAddFilterFlagWhenExtrasEmpty(): void
    {
        $criteria = new FilterCommand(
            query: 'abc',
            sort: 'id',
            sortDirection: 'DESC',
            page: 1,
            limit: 5
        );

        $params = $this->builder->build($criteria, []);

        self::assertSame([
            'query' => 'abc',
            'sort' => 'id',
            'sortDirection' => 'DESC',
            'page' => 1,
            'limit' => 5,
        ], $params);
        self::assertArrayNotHasKey('filter', $params);
    }

    public function testBuildDoesNotAddFilterFlagWhenDisabled(): void
    {
        $criteria = new FilterCommand(
            query: 'abc',
            sort: 'id',
            sortDirection: 'DESC',
            page: 1,
            limit: 5
        );

        $params = $this->builder->build($criteria, ['color' => 'red'], addFilterFlag: false);

        self::assertSame([
            'query' => 'abc',
            'sort' => 'id',
            'sortDirection' => 'DESC',
            'page' => 1,
            'limit' => 5,
            'color' => 'red',
        ], $params);
        self::assertArrayNotHasKey('filter', $params);
    }

    public function testBuildPrefersBaseValuesOverExtrasForSharedKeys(): void
    {
        $criteria = new FilterCommand(
            query: 'abc',
            sort: 'id',
            sortDirection: 'DESC',
            page: 1,
            limit: 5
        );

        $params = $this->builder->build($criteria, [
            'query' => 'override',
            'sort' => 'name',
            'sortDirection' => 'DESC',
            'page' => 99,
            'limit' => 999,
            'extra' => 'x',
        ]);

        self::assertSame('abc', $params['query']);
        self::assertSame('id', $params['sort']);
        self::assertSame('DESC', $params['sortDirection']);
        self::assertSame(1, $params['page']);
        self::assertSame(5, $params['limit']);
        self::assertSame('x', $params['extra']);
    }

    public function testMergeArraysMergesAndCleans(): void
    {
        $merged = $this->builder->mergeArrays(
            ['a' => '1', 'b' => null, 'c' => ''],
            ['b' => '2', 'd' => '3']
        );

        self::assertSame(['a' => '1', 'b' => '2', 'd' => '3'], $merged);
        self::assertArrayNotHasKey('c', $merged);
    }
}
