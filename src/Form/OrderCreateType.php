<?php

namespace App\Form;

use App\DTO\OrderCreateDto;
use App\Entity\ShippingMethod;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderCreateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customerId', TextType::class, [
                'label' => 'Customer Id'
            ])
            ->add('shippingMethod', EnumType::class, [
                'class' => ShippingMethod::class,
                'choice_label' => fn (ShippingMethod $shippingMethod) => $shippingMethod->getName(),
                'label' => 'Shipping Method',
                'placeholder' => 'Choose a Shipping Method',
            ])
            ->add('CustomerOrderRef')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrderCreateDto::class,
        ]);
    }
}
