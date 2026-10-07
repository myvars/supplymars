<?php

declare(strict_types=1);

namespace App\Shared\UI\Http\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A `Y-m-d` string picked from the kit DatePicker (rendered by `date_picker_widget` in the form theme).
 *
 * `side` is the edge of the field the calendar opens on. Use `top` for a field near the bottom of
 * a modal, where a calendar opening downwards would be cut off by the modal's scroll area.
 *
 * @extends AbstractType<string|null>
 */
final class DatePickerType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('side', 'bottom');
        $resolver->setAllowedValues('side', ['top', 'bottom']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['side'] = $options['side'];
    }

    public function getParent(): string
    {
        return TextType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'date_picker';
    }
}
