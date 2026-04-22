<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application;

use App\Shared\Application\RedirectTarget;
use App\Shared\Application\Result;
use PHPUnit\Framework\TestCase;

final class ResultTest extends TestCase
{
    public function testOkFactorySetsFieldsAndAllowsRedirect(): void
    {
        $redirect = new RedirectTarget('route_name', ['id' => 10], redirectStatus: 302);

        $result = Result::ok('done', ['payload' => 1], $redirect);

        self::assertTrue($result->ok);
        self::assertSame('done', $result->message);
        self::assertSame(['payload' => 1], $result->payload);
        self::assertSame($redirect, $result->redirect);
    }

    public function testFailFactorySetsFieldsAndNoRedirect(): void
    {
        $result = Result::fail('error', ['payload' => 'x']);

        self::assertFalse($result->ok);
        self::assertSame('error', $result->message);
        self::assertSame(['payload' => 'x'], $result->payload);
        self::assertNull($result->redirect);
    }
}
