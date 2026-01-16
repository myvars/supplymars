<?php

namespace App\Tests\Unit\Service\Search;

use App\Service\Crud\Common\CrudContext;
use App\Service\Search\SearchFilter;
use App\Shared\Application\Search\SearchFilterInterface;
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

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn(new \stdClass());

        ($this->searchFilter)($context);
    }

    public function testHandleWithValidSearchFilterInterfaceEntity(): void
    {
        $entity = $this->createMock(SearchFilterInterface::class);
        $entity->method('getQueryString')->willReturn('param1=value1&param2=value2');
        $entity->method('getSearchParams')->willReturn(['filter' => 'newFilter']);

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn($entity);
        $context->method('getSuccessLink')->willReturn('/search?param3=value3');

        $context->method('setIsUrlRefresh')->willReturnSelf();

        $context->expects($this->once())->method('setIsUrlRefresh')->with(true);
        $context->expects($this->once())->method('setSuccessLink')->with('/search?param1=value1&param2=value2&param3=value3&filter=newFilter');

        ($this->searchFilter)($context);
    }
}
