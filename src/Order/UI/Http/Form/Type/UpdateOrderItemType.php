<?php

declare(strict_types=1);

namespace App\Order\UI\Http\Form\Type;

use App\Order\UI\Http\Form\Model\UpdateOrderItemForm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<UpdateOrderItemForm>
 */
final class UpdateOrderItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('orderItemId', HiddenType::class)
            ->add('quantity')
            ->add('priceIncVat', MoneyType::class, [
                'currency' => 'GBP',
                'label' => 'Price Inc VAT',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateOrderItemForm::class,
        ]);
    }
}
