<?php

namespace App\Tests\Shared\UI\Http\FormFlow\View;

use App\Shared\UI\Http\FormFlow\View\ModelPath;
use PHPUnit\Framework\TestCase;

final class ModelPathTest extends TestCase
{
    public function testSnakeFlattensCamelCaseAfterLowercase(): void
    {
        self::assertSame('orderitem', ModelPath::snake('OrderItem'));
        self::assertSame('sales', ModelPath::snake('Sales'));
    }

    public function testPathWithBoundedContext(): void
    {
        self::assertSame('sales/orderitem/', ModelPath::path('Sales/OrderItem'));
    }

    public function testPathWithoutBoundedContext(): void
    {
        self::assertSame('orderitem/', ModelPath::path('OrderItem'));
    }

    public function testRouteWithBoundedContext(): void
    {
        self::assertSame('sales_orderitem', ModelPath::route('Sales/OrderItem'));
    }

    public function testRouteWithoutBoundedContext(): void
    {
        self::assertSame('orderitem', ModelPath::route('OrderItem'));
    }

    public function testTemplateDerivation(): void
    {
        self::assertSame('sales/orderitem/edit.html.twig', ModelPath::template('Sales/OrderItem', 'edit'));
        self::assertSame('orderitem/show.html.twig', ModelPath::template('OrderItem', 'show'));
    }

    public function testSnakeHandlesMixedDelimiters(): void
    {
        self::assertSame('sales_orderitem_extra', ModelPath::snake('Sales/OrderItem-Extra'));
    }
}
