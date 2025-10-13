<?php

namespace App\Tests\Integration\Service\Search;

use App\Service\Crud\Common\CrudContext;
use App\Service\Search\SearchFilter;
use App\Shared\Application\Search\SearchFilterInterface;
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

        $context = new CrudContext();
        $context->setEntity($entity);
        $context->setSuccessLink('/search?param3=value3');

        ($this->searchFilter)($context);

        $this->assertTrue($context->isUrlRefresh());
        $this->assertSame('/search?param1=value1&param2=value2&param3=value3&filter=newFilter', $context->getSuccessLink());
    }
}
