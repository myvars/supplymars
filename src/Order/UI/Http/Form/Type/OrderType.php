<?php

namespace App\Order\UI\Http\Form\Type;

use App\Order\UI\Http\Form\Model\OrderForm;
use App\Shared\Domain\ValueObject\ShippingMethod;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<OrderForm>
 */
final class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customerId', TextType::class, [
                'label' => 'Customer Id',
            ])
            ->add('shippingMethod', EnumType::class, [
                'class' => ShippingMethod::class,
                'choice_label' => fn (ShippingMethod $shippingMethod): string => $shippingMethod->getName(),
                'label' => 'Shipping Method',
                'placeholder' => 'Choose a Shipping Method',
            ])
            ->add('customerOrderRef')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrderForm::class,
        ]);
    }
}
