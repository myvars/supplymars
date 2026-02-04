<?php

namespace App\Tests\Shared\Application;

use App\Shared\Application\RedirectTarget;
use PHPUnit\Framework\TestCase;

final class RedirectTargetTest extends TestCase
{
    public function testDefaults(): void
    {
        $target = new RedirectTarget('route.name');

        self::assertSame('route.name', $target->route);
        self::assertSame([], $target->params);
        self::assertSame(303, $target->redirectStatus);
    }

    public function testCustomValues(): void
    {
        $target = new RedirectTarget('product.show', ['id' => 7], redirectStatus: 301);

        self::assertSame('product.show', $target->route);
        self::assertSame(['id' => 7], $target->params);
        self::assertSame(301, $target->redirectStatus);
    }
}
