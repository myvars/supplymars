<?php

declare(strict_types=1);

namespace App\Shared\UI\Http\Form\Model;

/**
 * Generic form model for inline editing of a single field.
 *
 * Usage:
 *   new InlineFieldForm($entity->getName())
 */
final class InlineFieldForm
{
    public function __construct(public ?string $value = null)
    {
    }
}
