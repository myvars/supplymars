<?php

namespace App\Tests\Integration\Service\Search;

use App\DTO\SearchDto\SearchFilterInterface;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Search\SearchFilter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SearchFilterIntegrationTest extends KernelTestCase
{
    private SearchFilter $searchFilter;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->searchFilter = new SearchFilter();
    }

    public function testHandleWithValidSearchFilterInterfaceEntity(): void
    {
        $entity = $this->createMock(SearchFilterInterface::class);
        $entity->method('getQueryString')->willReturn('param1=value1&param2=value2');
        $entity->method('getSearchParams')->willReturn(['filter' => 'newFilter']);

        $crudOptions = new CrudOptions();
        $crudOptions->setEntity($entity);
        $crudOptions->setSuccessLink('/search?param3=value3');

        $this->searchFilter->handle($crudOptions);

        $this->assertTrue($crudOptions->isUrlRefresh());
        $this->assertSame('/search?param1=value1&param2=value2&param3=value3&filter=newFilter', $crudOptions->getSuccessLink());
    }
}