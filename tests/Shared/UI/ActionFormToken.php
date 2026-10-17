<?php

declare(strict_types=1);

namespace App\Tests\Shared\UI;

use Zenstruck\Browser\KernelBrowser;

/**
 * Reads the CSRF token of a one-button action form (ActionFlow with ->csrf()) as a page renders it.
 */
trait ActionFormToken
{
    private function actionToken(KernelBrowser $browser, string $page, string $action): string
    {
        $token = (string) $browser
            ->visit($page)
            ->crawler()
            ->filter('form[action="' . $action . '"] input[name="_token"]')
            ->attr('value');

        self::assertNotSame('', $token);

        return $token;
    }
}
