<?php

declare(strict_types=1);

namespace App\Tests\Shared\UI;

use Zenstruck\Browser\KernelBrowser;

/**
 * Posts a form as the page renders it, plus a record id in a field the form doesn't have, and checks
 * the form refuses it. The record a form acts on comes from the route, never from a posted field.
 */
trait PostedRecordId
{
    /** @return iterable<string, array{string}> */
    public static function postedRecordIds(): iterable
    {
        yield 'another record' => ['other'];
        yield 'malformed' => ['x'];
        yield 'empty' => [''];
    }

    /**
     * @param string               $postedId 'other' posts $otherId, anything else is posted as it is
     * @param array<string, mixed> $changes  field values to change, so a post that got through would show
     */
    private function assertRefusesPostedRecordId(
        KernelBrowser $browser,
        string $page,
        string $button,
        string $field,
        string $postedId,
        string $otherId,
        array $changes = [],
    ): void {
        $values = $browser->visit($page)->crawler()->selectButton($button)->form()->getPhpValues();
        $formName = (string) array_key_first($values);
        $values[$formName] = [
            ...$values[$formName],
            ...$changes,
            $field => $postedId === 'other' ? $otherId : $postedId,
        ];

        $browser
            ->post($page, ['body' => $values])
            ->assertStatus(422)
            ->assertSee('This form should not contain extra fields.');
    }
}
