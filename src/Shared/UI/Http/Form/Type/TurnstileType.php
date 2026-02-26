<?php

namespace App\Shared\UI\Http\Form\Type;

use App\Shared\UI\Http\Validation\Turnstile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<string>
 */
final class TurnstileType extends AbstractType
{
    public function __construct(
        private readonly string $turnstileSiteKey,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped' => false,
            'error_bubbling' => false,
            'constraints' => [new Turnstile()],
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['site_key'] = $this->turnstileSiteKey;
    }

    #[\Override]
    public function getParent(): string
    {
        return HiddenType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'turnstile';
    }
}
