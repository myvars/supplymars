<?php

namespace App\Tests\Unit\Service\Search;

use App\DTO\SearchDto\SearchFilterInterface;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Search\SearchFilter;
use PHPUnit\Framework\TestCase;

class SearchFilterTest extends TestCase
{
    private SearchFilter $searchFilter;

    protected function setUp(): void
    {
        $this->searchFilter = new SearchFilter();
    }

    public function testHandleWithNonSearchFilterInterfaceEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must implement SearchFilterInterface');

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn(new \stdClass());

        $this->searchFilter->handle($crudOptions);
    }

    public function testHandleWithValidSearchFilterInterfaceEntity(): void
    {
        $entity = $this->createMock(SearchFilterInterface::class);
        $entity->method('getQueryString')->willReturn('param1=value1&param2=value2');
        $entity->method('getSearchParams')->willReturn(['filter' => 'newFilter']);

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn($entity);
        $crudOptions->method('getSuccessLink')->willReturn('/search?param3=value3');

        $crudOptions->method('setIsUrlRefresh')->willReturnSelf();

        $crudOptions->expects($this->once())->method('setIsUrlRefresh')->with(true);
        $crudOptions->expects($this->once())->method('setSuccessLink')->with('/search?param1=value1&param2=value2&param3=value3&filter=newFilter');

        $this->searchFilter->handle($crudOptions);
    }
}