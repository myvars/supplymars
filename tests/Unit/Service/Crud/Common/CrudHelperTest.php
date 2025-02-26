<?php

namespace App\Tests\Unit\Service\Crud\Common;

use App\Service\Crud\Common\CrudHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class CrudHelperTest extends TestCase
{
    private RequestStack $requestStack;
    private Environment $twig;
    private UrlGeneratorInterface $router;
    private CrudHelper $crudHelper;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->twig = $this->createMock(Environment::class);
        $this->router = $this->createMock(UrlGeneratorInterface::class);
        $this->crudHelper = new CrudHelper($this->requestStack, $this->twig, $this->router);
    }

    public function testSnakeCase(): void
    {
        $result = $this->crudHelper->snakeCase('Test String');
        $this->assertSame('test_string', $result);
    }

    public function testGetRequest(): void
    {
        $request = $this->createMock(Request::class);
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $result = $this->crudHelper->getRequest();
        $this->assertSame($request, $result);
    }

    public function testGetRouter(): void
    {
        $result = $this->crudHelper->getRouter();
        $this->assertSame($this->router, $result);
    }

    public function testShowEmpty(): void
    {
        $this->twig->method('render')->willReturn('content');

        $response = $this->crudHelper->showEmpty('section');
        $this->assertSame('content', $response->getContent());
        $this->assertSame(200, $response->getStatusCode());
    }
}