<?php

namespace App\Form\SearchForm;

use App\DTO\SearchDto\OrderSearchDto;
use App\Enum\OrderStatus;
use App\Form\DataTransformer\stringToOrderStatusTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderSearchFilterType extends AbstractType
{
    public function __construct(private readonly stringToOrderStatusTransformer $stringToOrderStatusTransformer)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customerOrderId', null, [
                'required' => false,
                'label' => 'Customer Order Id'
            ])
            ->add('purchaseOrderId', null, [
                'label' => 'Purchase Order Id'
            ])
            ->add('customerId', null, [
                'label' => 'Customer Id'
            ])
            ->add('productId', null, [
                'label' => 'with Product Id'
            ])
            ->add('orderStatus', EnumType::class, [
                'class' => OrderStatus::class,
                'choice_label' => fn (OrderStatus $orderStatus): string => $orderStatus->value,
                'label' => 'Order Status',
                'placeholder' => 'Any Order Status',
            ])
            ->add('startDate', TextType::class, [
                'label' => 'Start Date',
                'required' => false,
                'attr' => [
                    'data-controller' => 'datepicker',
                    'placeholder' => 'yyyy-mm-dd',
                ]
            ])
            ->add('endDate', TextType::class, [
                'label' => 'End Date',
                'required' => false,
                'attr' => [
                    'data-controller' => 'datepicker',
                    'placeholder' => 'yyyy-mm-dd',
                ]
            ])
            ->add('query', HiddenType::class)
            ->add('sort', HiddenType::class)
            ->add('sortDirection', HiddenType::class)
            ->add('page', HiddenType::class)
            ->add('limit', HiddenType::class)
        ;

        $builder->get('orderStatus')
            ->addModelTransformer($this->stringToOrderStatusTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrderSearchDto::class,
        ]);
    }
}
