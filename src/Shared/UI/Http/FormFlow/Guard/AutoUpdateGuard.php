<?php

namespace App\Shared\UI\Http\FormFlow\Guard;

use Symfony\Component\Form\FormInterface;

/**
 * Detects clicks on a dedicated `auto-update` submit and can clear form errors.
 */
final class AutoUpdateGuard
{
    private const string FIELD = 'auto-update';

    /**
     * True when the `auto-update` button exists and was clicked.
     */
    public function is(FormInterface $form): bool
    {
        return $form->has(self::FIELD) && $form->get(self::FIELD)->isClicked();
    }

    /**
     * Clears current form errors when in auto-update mode, if supported by the form.
     */
    public function clear(FormInterface $form): void
    {
        if ($this->is($form) && \method_exists($form, 'clearErrors')) {
            $form->clearErrors(true);
        }
    }
}
