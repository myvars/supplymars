<?php

declare(strict_types=1);

namespace App\Customer\UI\Http\Form\Type;

use App\Customer\UI\Http\Form\Model\RegistrationForm;
use App\Shared\UI\Http\Form\Type\TurnstileType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<RegistrationForm>
 */
final class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', null, [
                'priority' => 4,
                'attr' => ['placeholder' => 'e.g. Tim Apple'],
            ])
            ->add('email', null, [
                'attr' => ['placeholder' => 'name@company.com'],
            ])
            ->add('agreeTerms', CheckboxType::class)
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => true,
                'first_options' => ['label' => 'Password'],
                'second_options' => ['label' => 'Confirm password'],
                'attr' => ['autocomplete' => 'new-password'],
            ])
            ->add('turnstileResponse', TurnstileType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegistrationForm::class,
        ]);
    }
}
