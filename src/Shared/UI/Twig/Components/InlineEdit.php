<?php

declare(strict_types=1);

namespace App\Shared\UI\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Inline Edit Component (Turbo-native pattern).
 *
 * The component is a turbo-frame. Clicking the edit link replaces
 * the frame content with a form. Uses native Turbo preloading.
 *
 * Usage:
 *   <twig:InlineEdit
 *       editUrl="{{ path('app_catalog_manufacturer_inline_name', {id: manufacturer.publicId}) }}"
 *       frameId="inline-edit-manufacturer-{{ manufacturer.publicId }}-name"
 *   >
 *       {{ manufacturer.name }}
 *   </twig:InlineEdit>
 */
#[AsTwigComponent]
final class InlineEdit
{
    /**
     * URL to load the inline edit form (GET ?edit=1) and submit to (POST).
     */
    public string $editUrl;

    /**
     * Unique frame ID for this editable field.
     * Convention: inline-edit-{entity}-{publicId}-{field}.
     */
    public string $frameId;

    /**
     * Whether to show the edit icon (hover reveals by default).
     */
    public bool $showEditIcon = true;

    /**
     * Additional CSS classes for the turbo-frame.
     */
    public string $displayClass = '';

    /**
     * Icon to use for the edit link.
     */
    public string $editIcon = 'bi:pencil-square';

    /**
     * Size class for the edit icon.
     */
    public string $editIconSize = 'h-4 w-4';
}
