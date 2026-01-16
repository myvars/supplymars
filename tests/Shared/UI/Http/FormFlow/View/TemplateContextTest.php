<?php

namespace App\Tests\Shared\UI\Http\FormFlow\View;

use App\Shared\UI\Http\FormFlow\View\TemplateContext;
use PHPUnit\Framework\TestCase;

final class TemplateContextTest extends TestCase
{
    public function testFromBuildsExpectedDefaults(): void
    {
        $context = TemplateContext::from('Sales/OrderItem', 'edit');

        self::assertSame('OrderItem', $context->flowModel); // ucfirst of flattened model
        self::assertSame('sales_orderitem', $context->flowRoute);
        self::assertSame('sales/orderitem/', $context->flowPath);
        self::assertSame('edit', $context->flowOperation);
        self::assertSame('sales/orderitem/edit.html.twig', $context->template);
        self::assertSame([
            'flowModel' => 'OrderItem',
            'flowRoute' => 'sales_orderitem',
            'flowPath' => 'sales/orderitem/',
            'flowOperation' => 'edit',
            'template' => 'sales/orderitem/edit.html.twig',
        ], $context->toArray());
    }

    public function testFromWithTemplateOverride(): void
    {
        $context = TemplateContext::from('OrderItem', 'show', 'custom.html.twig');
        self::assertSame('OrderItem', $context->flowModel);
        self::assertSame('orderitem', $context->flowRoute);
        self::assertSame('orderitem/', $context->flowPath);
        self::assertSame('show', $context->flowOperation);
        self::assertSame('custom.html.twig', $context->template);
    }
}
