<?php

declare(strict_types=1);

namespace App\Tests\Shared\UI;

use Zenstruck\Browser\KernelBrowser;

/**
 * Checks the index page a filter form redirected to, and its whole query in any order. Browser's
 * assertOn() compares the query string in order, and shortens both URLs when it fails.
 */
trait FilterRedirect
{
    /**
     * @param array<string, scalar> $query
     */
    private function assertRedirectedWithQuery(KernelBrowser $browser, string $path, array $query): void
    {
        $url = parse_url($browser->client()->getRequest()->getUri());
        self::assertIsArray($url);
        self::assertSame($path, $url['path'] ?? null);

        parse_str($url['query'] ?? '', $actual);
        parse_str(http_build_query($query), $expected);
        ksort($actual);
        ksort($expected);

        self::assertSame($expected, $actual);
    }
}
