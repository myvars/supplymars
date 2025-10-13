<?php

namespace App\Customer\UI\Http\Form\Type;

use App\Customer\UI\Http\Form\Model\CustomerForm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CustomerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', HiddenType::class, [
                'required' => false,
            ])
            ->add('fullName')
            ->add('email')
            ->add('isVerified', CheckboxType::class, ['label' => 'Verified'])
            ->add('isStaff', CheckboxType::class, ['label' => 'Member of Staff'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CustomerForm::Class,
        ]);
    }
}
