<?php

declare(strict_types=1);

namespace App\Tests\Shared\UI;

use Zenstruck\Browser\KernelBrowser;

/**
 * Saves a value through an inline edit field (InlineEditFlow) and checks the response. The caller
 * logs in, and checks the entity afterwards: unchanged after a refusal.
 */
trait InlineRename
{
    /**
     * @param string $field the inline edit URL without the query, e.g. /product/{id}/inline/name
     */
    private function assertInlineEditRefused(KernelBrowser $browser, string $field, string $value, string $error): void
    {
        $browser
            ->get($field . '?edit=1')
            ->fillField('inline_field[value]', $value)
            ->click('Save (Enter)')
            ->assertStatus(422)
            ->assertSeeIn('form', $error) // the field's error, rendered inside the form
            ->assertSeeElement('input[name="inline_field[value]"]');
    }

    /**
     * The response only tells Turbo to refresh; the page then shows the new name in its breadcrumb.
     */
    private function assertInlineRenameRefreshes(KernelBrowser $browser, string $field, string $page): void
    {
        $browser
            ->get($field . '?edit=1')
            ->fillField('inline_field[value]', 'Renamed Value')
            ->click('Save (Enter)')
            ->assertSuccessful()
            ->assertContains('<turbo-stream action="refresh" scroll="preserve"></turbo-stream>')
            ->assertNotContains('Renamed Value')
            ->get($page)
            ->assertSeeIn('nav[aria-label="breadcrumb"]', 'Renamed Value');
    }
}
