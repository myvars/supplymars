<?php

declare(strict_types=1);

namespace App\Shared\UI\Http\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * A `Y-m-d` string picked from the kit DatePicker (rendered by `date_picker_widget` in the form theme).
 *
 * @extends AbstractType<string|null>
 */
final class DatePickerType extends AbstractType
{
    public function getParent(): string
    {
        return TextType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'date_picker';
    }
}
